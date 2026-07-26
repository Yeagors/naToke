<?php

namespace App\Http\Controllers;

use App\Enums\TariffPeriod;
use App\Models\Car;
use App\Models\Lead;
use App\Models\Rental;
use App\Models\Tariff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * ВРЕМЕННЫЙ демо-контроллер: живой AI-менеджер проката/выкупа электровелосипедов
 * naToke. Берёт реальные модели и тарифы из БД. Удаляется целиком вместе с
 * роутами /ai-seller-demo/* и вьюхой resources/views/ai-seller-demo.blade.php.
 *
 * Требует ANTHROPIC_API_KEY в .env (запросы идут через релей ANTHROPIC_BASE_URL,
 * т.к. api.anthropic.com недоступен с российского IP).
 */
class AiSellerDemoController extends Controller
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const MODEL   = 'claude-haiku-4-5-20251001';

    public function index()
    {
        return view('ai-seller-demo', ['hasKey' => (bool) config('services.anthropic.key')]);
    }

    public function chat(Request $request): JsonResponse
    {
        $key = config('services.anthropic.key');
        if (! $key) {
            return response()->json(['error' => 'Не задан ANTHROPIC_API_KEY в .env (после правки: php artisan config:cache).'], 400);
        }

        $messages = $request->input('messages', []);
        if (! is_array($messages) || empty($messages)) {
            return response()->json(['error' => 'Пустой диалог.'], 422);
        }

        // api.anthropic.com недоступен с российского IP → при желании ходим через
        // релей (base_url, напр. Cloudflare Worker) и/или прокси вне РФ.
        $apiUrl = config('services.anthropic.base_url') ?: self::API_URL;
        $proxy  = config('services.anthropic.proxy');

      try {
        $toolLog = [];
        // Агентный цикл: крутим, пока модель просит инструменты (лимит от зацикливания).
        for ($i = 0; $i < 8; $i++) {
            // Релей/сеть иногда сбрасывают соединение (cURL 56) — ретраим
            // транзиентные сбои и не даём исключению уронить запрос в 500.
            try {
                $resp = Http::withHeaders([
                    'x-api-key'         => $key,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ])->when($proxy, fn ($h) => $h->withOptions(['proxy' => $proxy]))
                  ->retry(3, 400, throw: false)
                  ->timeout(60)->post($apiUrl, [
                    'model'      => self::MODEL,
                    'max_tokens' => 1024,
                    'system'     => $this->systemPrompt(),
                    'tools'      => $this->toolsSchema(),
                    // Санитизируем всю историю: пустой tool_use.input ({} из JSON)
                    // на стороне PHP становится [] и кодируется в JSON-массив, что
                    // Anthropic отвергает ("Input should be an object").
                    'messages'   => $this->fixToolInputs($messages),
                ]);
            } catch (\Throwable $e) {
                report($e);
                return response()->json(['error' => 'Сервис ИИ временно недоступен, попробуйте ещё раз.'], 502);
            }

            if ($resp->failed()) {
                report(new \RuntimeException('Anthropic API '.$resp->status().': '.$resp->body()));
                return response()->json(['error' => 'Сервис ИИ временно недоступен, попробуйте ещё раз.'], 502);
            }

            $data    = $resp->json();
            $content = $data['content'] ?? [];
            $messages[] = ['role' => 'assistant', 'content' => $content];

            if (($data['stop_reason'] ?? '') !== 'tool_use') {
                break;
            }

            $results = [];
            foreach ($content as $block) {
                if (($block['type'] ?? '') !== 'tool_use') {
                    continue;
                }
                $out = $this->executeTool($block['name'], (array) ($block['input'] ?? []));
                $toolLog[] = ['name' => $block['name'], 'input' => $block['input'] ?? [], 'output' => $out];
                $results[] = [
                    'type'        => 'tool_result',
                    'tool_use_id' => $block['id'],
                    'content'     => json_encode($out, JSON_UNESCAPED_UNICODE),
                ];
            }
            $messages[] = ['role' => 'user', 'content' => $results];
        }

        // Собираем видимый текст последнего ответа ассистента.
        $reply = '';
        $last  = end($messages);
        if (($last['role'] ?? '') === 'assistant') {
            foreach ((array) $last['content'] as $b) {
                if (($b['type'] ?? '') === 'text') {
                    $reply .= $b['text'];
                }
            }
        }

        return response()->json(['reply' => trim($reply), 'tools' => $toolLog, 'messages' => $messages]);
      } catch (\Throwable $e) {
        // Последний рубеж: ничто в обработке диалога не должно вернуть голый 500.
        report($e);
        return response()->json(['error' => 'Что-то пошло не так, попробуйте ещё раз.'], 500);
      }
    }

    /**
     * Приводит пустой tool_use.input ([] в PHP) к объекту {} во всей истории —
     * иначе Anthropic отвергает запрос ("Input should be an object"). Возвращает
     * копию (сам $messages остаётся с массивами — удобно для executeTool).
     */
    private function fixToolInputs(array $messages): array
    {
        foreach ($messages as &$msg) {
            if (! is_array($msg['content'] ?? null)) {
                continue;
            }
            foreach ($msg['content'] as &$block) {
                if (is_array($block)
                    && ($block['type'] ?? '') === 'tool_use'
                    && ! is_object($block['input'] ?? null)
                    && empty($block['input'])) {
                    $block['input'] = new \stdClass();
                }
            }
            unset($block);
        }
        unset($msg);

        return $messages;
    }

    /* ─────────────────────────── Данные из БД ─────────────────────────── */

    /** Модели в наличии: сгруппированы, со свободным остатком. Закуп НЕ раскрываем. */
    private function models(): array
    {
        $busy = array_flip(
            Rental::whereIn('status', ['open', 'paused'])->pluck('car_id')->all()
        );

        $groups = [];
        foreach (Car::all(['id', 'brand', 'model', 'year', 'battery_capacity']) as $c) {
            $key = trim("{$c->brand} {$c->model}");
            $groups[$key] ??= ['name' => $key, 'total' => 0, 'available' => 0, 'battery' => null, 'year' => $c->year];
            $groups[$key]['total']++;
            if (! isset($busy[$c->id])) {
                $groups[$key]['available']++;
            }
            if (! $groups[$key]['battery'] && $c->battery_capacity) {
                $groups[$key]['battery'] = $c->battery_capacity;
            }
        }

        return array_values($groups);
    }

    /** Активные тарифы (аренда + раскат/выкуп). Мусорные (< 100 ₽) отсекаем. */
    private function tariffs(): array
    {
        return Tariff::where('is_active', true)
            ->where('amount', '>=', 100)
            ->orderBy('is_buyout')
            ->orderBy('amount')
            ->get()
            ->map(function (Tariff $t) {
                $payments = ($t->is_buyout && $t->buyout_price && (float) $t->amount > 0)
                    ? (int) ceil((float) $t->buyout_price / (float) $t->amount)
                    : null;

                return array_filter([
                    'id'             => $t->id,
                    'name'           => $t->name,
                    'type'           => $t->is_buyout ? 'выкуп (раскат)' : 'аренда',
                    'payment'        => (float) $t->amount,
                    'per'            => $this->periodWord($t->period),
                    'deposit'        => (float) $t->deposit_amount,
                    'buyout_price'   => $t->is_buyout ? (float) $t->buyout_price : null,
                    'payments_count' => $payments,
                ], fn ($v) => $v !== null && $v !== '');
            })
            ->values()
            ->all();
    }

    private function periodWord(TariffPeriod $p): string
    {
        return match ($p) {
            TariffPeriod::Minute => 'минуту',
            TariffPeriod::Hour   => 'час',
            TariffPeriod::Day    => 'день',
            TariffPeriod::Week   => 'неделю',
            TariffPeriod::Month  => 'месяц',
        };
    }

    /* ─────────────────────────── Инструменты ─────────────────────────── */

    private function executeTool(string $name, array $in): array
    {
        try {
            return $this->dispatchTool($name, $in);
        } catch (\Throwable $e) {
            report($e);

            return ['error' => 'Не удалось выполнить действие. Скажи клиенту, что заявку зафиксируем, менеджер свяжется.'];
        }
    }

    private function dispatchTool(string $name, array $in): array
    {
        return match ($name) {
            'list_models' => ['models' => array_map(fn ($m) => [
                'name'      => $m['name'],
                'available' => $m['available'],
                'battery'   => $m['battery'] ?: 'уточняется',
            ], $this->models())],

            'get_tariffs' => ['tariffs' => $this->tariffs()],

            'calc_buyout' => $this->calcBuyout((int) ($in['tariff_id'] ?? 0)),

            'check_availability' => $this->checkAvailability((string) ($in['model'] ?? '')),

            'book_visit' => $this->bookVisit($in),

            'handoff_to_manager' => $this->handoff($in),

            'record_outcome' => $this->recordOutcome($in),

            default => ['error' => "Неизвестный инструмент: {$name}"],
        };
    }

    private function calcBuyout(int $tariffId): array
    {
        $t = Tariff::find($tariffId);
        if (! $t || ! $t->is_buyout || ! $t->buyout_price) {
            return ['error' => 'Тариф выкупа не найден'];
        }

        $payment  = (float) $t->amount;
        $payments = $payment > 0 ? (int) ceil((float) $t->buyout_price / $payment) : 0;

        return [
            'tariff'         => $t->name,
            'payment'        => $payment,
            'per'            => $this->periodWord($t->period),
            'deposit'        => (float) $t->deposit_amount,
            'buyout_price'   => (float) $t->buyout_price,
            'payments_count' => $payments,
            'note'           => 'Платежи идут в счёт выкупа — по завершении велосипед переходит в собственность. Залог и точные условия фиксируются в договоре.',
        ];
    }

    private function checkAvailability(string $model): array
    {
        $model = mb_strtolower(trim($model));
        foreach ($this->models() as $m) {
            if ($model !== '' && str_contains(mb_strtolower($m['name']), $model)) {
                return [
                    'model'     => $m['name'],
                    'available' => $m['available'],
                    'in_stock'  => $m['available'] > 0,
                    'battery'   => $m['battery'] ?: 'уточняется',
                ];
            }
        }

        return ['in_stock' => false, 'note' => 'Такой модели у нас нет. Предложи из наличия (list_models).'];
    }

    /** Запись на выдачу: сохраняем лид в CRM и шлём уведомление в Telegram. */
    private function bookVisit(array $in): array
    {
        $name  = trim((string) ($in['name'] ?? ''));
        $phone = trim((string) ($in['phone'] ?? ''));
        if ($name === '' || $phone === '') {
            return ['status' => 'need_contact', 'note' => 'Нужны имя и телефон для записи.'];
        }

        $ref  = 'NT-'.mb_strtoupper(substr(md5($name.$phone.($in['when'] ?? '')), 0, 6));
        $this->saveAndNotify([
            'name'     => $name,
            'phone'    => $phone,
            'model'    => $this->cleanField($in['model'] ?? null) ?? 'не определился',
            'tariff'   => $this->cleanField($in['tariff'] ?? null),
            'visit_at' => $this->cleanField($in['when'] ?? null),
            'result'   => 'booked',
            'summary'  => $this->cleanField($in['summary'] ?? null),
            'ref'      => $ref,
        ]);

        return [
            'status'     => 'booked',
            'booking_id' => $ref,
            'note'       => 'Записал на выдачу. Менеджер подтвердит адрес и время. С собой — паспорт.',
        ];
    }

    /** Передача живому менеджеру: фиксируем лид + уведомление. */
    private function handoff(array $in): array
    {
        $this->saveAndNotify([
            'name'    => $this->cleanField($in['name'] ?? null),
            'phone'   => $this->cleanField($in['phone'] ?? null),
            'model'   => $this->cleanField($in['model'] ?? null),
            'result'  => 'handoff',
            'reason'  => $this->cleanField($in['reason'] ?? null),
            'summary' => $this->cleanField($in['summary'] ?? null),
        ]);

        return ['status' => 'handed_off', 'note' => 'Передал живому менеджеру, свяжется в ближайшее время.'];
    }

    /** Фиксация исхода для аналитики: клиент думает или отказался. */
    private function recordOutcome(array $in): array
    {
        $result = ($in['result'] ?? '') === 'reject' ? 'reject' : 'think';
        $this->saveAndNotify([
            'name'    => $this->cleanField($in['name'] ?? null),
            'phone'   => $this->cleanField($in['phone'] ?? null),
            'model'   => $this->cleanField($in['model'] ?? null),
            'result'  => $result,
            'reason'  => $this->cleanField($in['reason'] ?? null),
            'summary' => $this->cleanField($in['summary'] ?? null),
        ]);

        return ['status' => 'recorded'];
    }

    /** Нормализация поля от модели: обрезаем пробелы, пустое → null. */
    private function cleanField(mixed $v): ?string
    {
        if (! is_scalar($v)) {
            return null;
        }
        $v = trim((string) $v);

        return $v === '' ? null : $v;
    }

    /**
     * Сохраняем лид и шлём уведомление — каждый шаг изолирован: сбой БД или
     * Telegram не должен ронять запрос. Заявка формируется при любом входе.
     */
    private function saveAndNotify(array $data): void
    {
        try {
            $lead = Lead::create(array_merge(['source' => 'ai_demo'], $data));
        } catch (\Throwable $e) {
            report($e);
            return;
        }

        try {
            $this->notifyTelegram($lead);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Карточка заявки в общий чат. Тихо no-op, если бот/чат не настроены. */
    private function notifyTelegram(Lead $lead): void
    {
        $token  = config('services.telegram.bot_token');
        $chatId = config('services.telegram.leads_chat_id');
        if (! $token || ! $chatId) {
            return;
        }
        // api.telegram.org недоступен с РФ-хостинга → при наличии ходим через релей.
        $base = rtrim(config('services.telegram.base_url') ?: 'https://api.telegram.org', '/');

        $icon = match ($lead->result) {
            'booked'  => '🎫',
            'handoff' => '🙋',
            'reject'  => '🚫',
            default   => '💬',
        };
        $lines = ["{$icon} <b>{$lead->result_label}</b> · наТоке (AI-демо)"];
        if ($lead->name || $lead->phone) {
            $lines[] = '👤 '.trim(($lead->name ?: '').' '.($lead->phone ? '· '.$lead->phone : ''));
        }
        if ($lead->model || $lead->tariff) {
            $lines[] = '🛴 '.trim(($lead->model ?: '').($lead->tariff ? ' · '.$lead->tariff : ''));
        }
        if ($lead->visit_at) {
            $lines[] = '📅 '.$lead->visit_at;
        }
        if ($lead->reason) {
            $lines[] = '❗ '.$lead->reason;
        }
        if ($lead->summary) {
            $lines[] = '📝 '.$lead->summary;
        }

        try {
            Http::timeout(10)->post("{$base}/bot{$token}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => implode("\n", array_map('strval', $lines)),
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function toolsSchema(): array
    {
        return [
            [
                'name' => 'list_models',
                'description' => 'Список моделей электровелосипедов в наличии: название, сколько свободно, аккумулятор.',
                'input_schema' => ['type' => 'object', 'properties' => (object) []],
            ],
            [
                'name' => 'get_tariffs',
                'description' => 'Актуальные тарифы: аренда (помесячно) и раскат/выкуп (еженедельные платежи в счёт выкупа). Цены, залог, выкупная стоимость, число платежей. ВСЕГДА бери цены отсюда, не выдумывай.',
                'input_schema' => ['type' => 'object', 'properties' => (object) []],
            ],
            [
                'name' => 'calc_buyout',
                'description' => 'Расклад по конкретному тарифу выкупа: платёж, число платежей, залог, полная выкупная стоимость.',
                'input_schema' => ['type' => 'object', 'properties' => ['tariff_id' => ['type' => 'integer', 'description' => 'ID тарифа из get_tariffs']], 'required' => ['tariff_id']],
            ],
            [
                'name' => 'check_availability',
                'description' => 'Проверить наличие конкретной модели.',
                'input_schema' => ['type' => 'object', 'properties' => ['model' => ['type' => 'string', 'description' => 'Название модели, напр. Kugoo U5']], 'required' => ['model']],
            ],
            [
                'name' => 'book_visit',
                'description' => 'Записать клиента на выдачу велосипеда. Нужны имя и телефон; желательно время, модель и тариф. Заявка попадает менеджерам.',
                'input_schema' => ['type' => 'object', 'properties' => [
                    'name' => ['type' => 'string'], 'phone' => ['type' => 'string'],
                    'when' => ['type' => 'string', 'description' => 'Конкретная дата визита, напр. «20 июля, 13:00». НЕ относительная: переведи «завтра»/«в субботу» в реальную дату от сегодняшней.'],
                    'model' => ['type' => 'string'], 'tariff' => ['type' => 'string'],
                    'summary' => ['type' => 'string', 'description' => 'Короткая выжимка диалога (1-2 фразы): что хотел клиент и о чём договорились'],
                ], 'required' => ['name', 'phone']],
            ],
            [
                'name' => 'handoff_to_manager',
                'description' => 'Передать диалог живому менеджеру (нестандартные условия, жалобы, юр. вопросы).',
                'input_schema' => ['type' => 'object', 'properties' => [
                    'reason' => ['type' => 'string'],
                    'name' => ['type' => 'string'], 'phone' => ['type' => 'string'], 'model' => ['type' => 'string'],
                    'summary' => ['type' => 'string', 'description' => 'Короткая выжимка диалога (1-2 фразы)'],
                ], 'required' => ['reason']],
            ],
            [
                'name' => 'record_outcome',
                'description' => 'Зафиксировать исход диалога для аналитики, когда клиент НЕ записался: явно отказался (result=reject, укажи причину) или ушёл думать (result=think). Вызывай в конце такого диалога один раз.',
                'input_schema' => ['type' => 'object', 'properties' => [
                    'result' => ['type' => 'string', 'enum' => ['reject', 'think'], 'description' => 'reject — отказ, think — думает'],
                    'reason' => ['type' => 'string', 'description' => 'Причина: дорого / уже купил / не тот город / не устроили условия и т.п.'],
                    'model' => ['type' => 'string'], 'name' => ['type' => 'string'], 'phone' => ['type' => 'string'],
                    'summary' => ['type' => 'string', 'description' => 'Короткая выжимка диалога (1-2 фразы)'],
                ], 'required' => ['result', 'reason']],
            ],
        ];
    }

    private function systemPrompt(): string
    {
        $today = \Carbon\Carbon::now('Europe/Moscow')->locale('ru');
        $dateLine = 'Сегодня '.$today->isoFormat('dddd, D MMMM YYYY').' (по Москве).';

        return <<<TXT
Ты — менеджер сервиса проката электровелосипедов «наТоке». Общаешься с клиентом в мессенджере.

{$dateLine}

Что мы предлагаем:
- АРЕНДА — платишь помесячно и катаешься, велосипед остаётся наш.
- РАСКАТ (выкуп) — вносишь еженедельные платежи, они идут в счёт выкупа; по завершении велосипед становится твоим. Есть залог.

Стиль: коротко, по-человечески, дружелюбно, без канцелярита. Одно-два предложения на реплику. Эмодзи — умеренно.

Правила:
1. Говори только о реальных моделях и тарифах — бери их из инструментов (list_models, get_tariffs). Не выдумывай модели, цены, сроки и характеристики.
2. Цены, залог, выкупную стоимость и число платежей называй ТОЛЬКО из get_tariffs / calc_buyout. Никогда не считай и не угадывай суммы сам.
3. Понятно объясняй разницу между арендой и раскатом, помогай выбрать под задачу клиента.
4. Не вызывай инструменты, о которых не просили. Отвечай ровно на заданный вопрос.
5. Для записи на выдачу возьми имя и телефон, уточни удобное время и интересующую модель/тариф, затем вызови book_visit с короткой выжимкой диалога. Скажи, что менеджер подтвердит адрес и время, с собой паспорт.
6. В поле when у book_visit пиши КОНКРЕТНУЮ дату, а не относительную. Отсчитывай от сегодняшней даты (см. выше) и переводи: «завтра в 13» → «20 июля, 13:00», «в субботу днём» → «25 июля, днём», «сегодня вечером» → «19 июля, вечером». Никогда не пиши «завтра», «в субботу», «послезавтра» — только реальную дату. С клиентом в переписке можешь говорить как удобно, но в заявку записывай точную дату.
7. Если модели нет в наличии — честно скажи и предложи что есть.
8. Нестандартные условия, жалобы, юр. вопросы — вызывай handoff_to_manager.
9. Если клиент НЕ записался — в конце диалога один раз вызови record_outcome: result=reject с причиной (дорого / уже купил / не тот город и т.п.), либо result=think если ушёл думать. Это нужно для аналитики отказов. Не приставай к клиенту из-за этого — просто зафиксируй.
10. Никогда не раскрывай внутреннюю/закупочную стоимость — ты её не знаешь. Валюта — рубли.
TXT;
    }
}
