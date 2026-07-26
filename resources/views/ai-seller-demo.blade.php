<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>наТоке · AI-менеджер проката (демо)</title>
<style>
  :root{--bg:#0e131a;--panel:#151b24;--in:#202a38;--out:#2b5278;--tool:#0b0f15;--line:#243040;--tx:#e9eef5;--mut:#8a97a8;--acc:#5aa2f0}
  *{box-sizing:border-box}
  body{margin:0;background:var(--bg);color:var(--tx);font-family:system-ui,"Segoe UI",Roboto,sans-serif;
    display:flex;align-items:center;justify-content:center;min-height:100vh;padding:16px}
  .app{width:100%;max-width:560px;height:min(90vh,760px);display:flex;flex-direction:column;
    background:var(--panel);border:1px solid var(--line);border-radius:14px;overflow:hidden}
  header{display:flex;align-items:center;gap:11px;padding:12px 16px;border-bottom:1px solid var(--line);background:#12181f}
  .ava{width:40px;height:40px;border-radius:50%;background:#26527d;display:flex;align-items:center;justify-content:center;font-size:19px}
  header .t{font-weight:600;font-size:15px}
  header .s{font-size:12px;color:var(--mut)}
  .s .on{color:#4bd07b}
  #feed{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:9px}
  .msg{max-width:82%;padding:9px 13px;border-radius:14px;font-size:14.5px;line-height:1.5;white-space:pre-wrap;word-wrap:break-word}
  .me{align-self:flex-end;background:var(--out);border-bottom-right-radius:4px}
  .ai{align-self:flex-start;background:var(--in);border-bottom-left-radius:4px}
  .ai a{color:#8fc0ff}
  .tool{align-self:stretch;font-family:ui-monospace,Consolas,monospace;font-size:12px;color:var(--mut);
    background:var(--tool);border:1px solid var(--line);border-radius:8px;padding:6px 9px}
  .tool b{color:#c8b06a;font-weight:600}
  .floor{color:#e8746a}
  .typing{align-self:flex-start;color:var(--mut);font-size:13px;padding:4px 6px}
  form{display:flex;gap:8px;padding:12px 14px;border-top:1px solid var(--line);background:#12181f}
  input[type=text]{flex:1;background:var(--in);border:1px solid var(--line);border-radius:10px;color:var(--tx);
    padding:11px 13px;font-size:14.5px;outline:none}
  input[type=text]:focus{border-color:var(--acc)}
  button{background:var(--acc);border:0;color:#06152a;font-weight:600;border-radius:10px;padding:0 16px;cursor:pointer;font-size:14.5px}
  button:disabled{opacity:.5;cursor:default}
  .hints{display:flex;flex-wrap:wrap;gap:6px;padding:0 14px 12px;background:#12181f}
  .hint{font-size:12.5px;color:var(--tx);background:var(--in);border:1px solid var(--line);border-radius:14px;padding:6px 11px;cursor:pointer}
  .warn{margin:14px;padding:12px 14px;background:#3a2416;border:1px solid #6b4620;border-radius:10px;font-size:13.5px;color:#f0c89a}
</style>
</head>
<body>
<div class="app">
  <header>
    <div class="ava">🛴</div>
    <div>
      <div class="t">Максим · наТоке</div>
      <div class="s"><span class="on">●</span> менеджер проката · онлайн 24/7</div>
    </div>
  </header>

  @unless($hasKey)
    <div class="warn">⚠️ Не задан <b>ANTHROPIC_API_KEY</b> в <code>.env</code>. Добавьте ключ и выполните <code>php artisan config:clear</code> — тогда агент оживёт.</div>
  @endunless

  <div id="feed"></div>

  <div class="hints" id="hints">
    <span class="hint">Что есть в наличии?</span>
    <span class="hint">Как работает раскат (выкуп)?</span>
    <span class="hint">Сколько стоит выкупить велосипед?</span>
    <span class="hint">А просто арендовать на месяц?</span>
    <span class="hint">Есть Kugoo U5?</span>
    <span class="hint">Хочу записаться на выдачу</span>
  </div>

  <form id="form" autocomplete="off">
    <input type="text" id="inp" placeholder="Напишите сообщение…" {{ $hasKey ? '' : 'disabled' }}>
    <button type="submit" id="send" {{ $hasKey ? '' : 'disabled' }}>▶</button>
  </form>
</div>

<script>
const CSRF = document.querySelector('meta[name=csrf-token]').content;
const feed = document.getElementById('feed');
const inp = document.getElementById('inp');
const send = document.getElementById('send');
let messages = [];   // Anthropic-формат, растёт между ходами

function esc(s){const d=document.createElement('div');d.textContent=s;return d.innerHTML;}
function linkify(s){return esc(s).replace(/(https?:\/\/[^\s]+)/g,'<a href="$1" target="_blank" rel="noopener">$1</a>');}

function addMsg(cls, html){
  const d = document.createElement('div');
  d.className = 'msg ' + cls;
  d.innerHTML = html;
  feed.appendChild(d); feed.scrollTop = feed.scrollHeight;
  return d;
}
function addTool(t){
  const d = document.createElement('div');
  d.className = 'tool';
  const brief = JSON.stringify(t.output);
  let extra = '';
  if (t.name === 'propose_price') extra = ' <span class="floor">[закупочный «пол» скрыт от модели]</span>';
  d.innerHTML = '⚙ <b>'+esc(t.name)+'</b>('+esc(JSON.stringify(t.input))+') → '+esc(brief)+extra;
  feed.appendChild(d); feed.scrollTop = feed.scrollHeight;
}

async function greet(){
  addMsg('ai','Здравствуйте! Я AI-менеджер проката наТоке, на связи круглосуточно 🛴 Помогу выбрать электровелосипед — в аренду или на выкуп (раскат). Что интересует?');
}

async function ask(text){
  addMsg('me', esc(text));
  messages.push({role:'user', content:text});
  inp.value=''; inp.disabled=true; send.disabled=true;
  const typing = addMsg('typing','Максим печатает…'); typing.className='typing';

  try{
    const r = await fetch('{{ route('ai.demo.chat') }}', {
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
      body: JSON.stringify({messages})
    });
    const data = await r.json();
    typing.remove();
    if(!r.ok || data.error){ addMsg('ai','⚠️ '+esc(data.error||('Ошибка '+r.status))); }
    else{
      if(data.reply) addMsg('ai', linkify(data.reply));
      messages = data.messages;   // канонич. история с сервера
    }
  }catch(e){ typing.remove(); addMsg('ai','⚠️ Сеть недоступна: '+esc(e.message)); }
  finally{ inp.disabled=false; send.disabled=false; inp.focus(); }
}

document.getElementById('form').addEventListener('submit', e=>{
  e.preventDefault();
  const t = inp.value.trim();
  if(t) ask(t);
});
document.getElementById('hints').addEventListener('click', e=>{
  if(e.target.classList.contains('hint') && !inp.disabled) ask(e.target.textContent);
});

greet();
</script>
</body>
</html>
