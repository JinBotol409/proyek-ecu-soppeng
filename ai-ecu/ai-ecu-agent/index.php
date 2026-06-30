<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>AI Diagnosa ECU - IDS Repair</title>

<style>
*{
margin:0;
padding:0;
box-sizing:border-box;
}

body{
font-family:Arial,sans-serif;
background:#050505;
color:#fff;
min-height:100vh;
overflow-x:hidden;
}

.ai-wrap{
width:100%;
max-width:850px;
margin:auto;
padding:14px;
}

.ai-box{
background:#111;
border:1px solid #2a2a2a;
border-radius:18px;
padding:16px;
box-shadow:0 0 25px rgba(255,0,0,.18);
min-height:calc(100vh - 28px);
display:flex;
flex-direction:column;
}

.ai-head{
margin-bottom:10px;
}

.ai-head h1{
color:#ff2b2b;
font-size:26px;
margin-bottom:8px;
}

.ai-head p{
color:#ccc;
line-height:1.6;
font-size:14px;
}

.quick{
display:grid;
grid-template-columns:repeat(2,1fr);
gap:8px;
margin:12px 0;
}

.quick button{
padding:10px 8px;
font-size:12px;
background:#222;
border:1px solid #333;
border-radius:12px;
color:#fff;
font-weight:bold;
}

#chat{
flex:1;
min-height:260px;
max-height:48vh;
overflow-y:auto;
background:#080808;
border:1px solid #222;
border-radius:14px;
padding:12px;
margin:8px 0 12px;
}

.msg{
padding:12px;
border-radius:13px;
margin-bottom:10px;
line-height:1.55;
white-space:pre-wrap;
font-size:14px;
}

.user{
background:#8b0000;
margin-left:40px;
border-bottom-right-radius:4px;
}

.bot{
background:#1b1b1b;
border-left:3px solid #ff2b2b;
margin-right:40px;
border-bottom-left-radius:4px;
}

.input-row{
display:grid;
grid-template-columns:1fr 86px;
gap:10px;
align-items:stretch;
}

textarea{
width:100%;
min-height:58px;
max-height:110px;
background:#0b0b0b;
border:1px solid #333;
color:#fff;
border-radius:12px;
padding:12px;
resize:none;
font-size:14px;
outline:none;
}

.input-row button{
background:#d00000;
color:#fff;
border:none;
border-radius:12px;
font-weight:bold;
font-size:15px;
}

.note{
font-size:11px;
color:#999;
line-height:1.5;
margin-top:10px;
}

@media(max-width:480px){

.ai-wrap{
padding:10px;
}

.ai-box{
padding:14px;
border-radius:16px;
min-height:calc(100vh - 20px);
}

.ai-head h1{
font-size:24px;
}

.ai-head p{
font-size:13px;
}

.quick{
grid-template-columns:repeat(2,1fr);
gap:7px;
}

.quick button{
font-size:11px;
padding:9px 6px;
}

#chat{
max-height:42vh;
min-height:250px;
padding:10px;
}

.msg{
font-size:13px;
padding:11px;
}

.user{
margin-left:28px;
}

.bot{
margin-right:28px;
}

.input-row{
grid-template-columns:1fr 78px;
gap:8px;
}

textarea{
font-size:13px;
min-height:56px;
}

.input-row button{
font-size:14px;
}
}
</style>
</head>

<body>

<div class="ai-wrap">

<div class="ai-box">

<div class="ai-head">

<h1>
🤖 AI Diagnosa ECU
</h1>

<p>
Tanya keluhan ECU, DTC, no start, no communication, IMMO, injector, CKP/CMP,
atau prosedur read/write ECU.
</p>

</div>

<div class="quick">

<button onclick="setQ('Mobil no start, scanner tidak bisa komunikasi ke ECU. Apa yang harus dicek?')">
No Communication
</button>

<button onclick="setQ('Apa penyebab injector tidak keluar sinyal dari ECU?')">
Injector Mati
</button>

<button onclick="setQ('Jelaskan cara kerja ECU mobil secara sederhana')">
Cara Kerja ECU
</button>

<button onclick="setQ('Apa risiko saat proses write file ECU?')">
Risiko Write ECU
</button>

</div>

<div id="chat">

<div class="msg bot">
Halo, saya AI ECU IDS Repair. Jelaskan keluhan kendaraan, tipe mobil, kode DTC, dan gejala lengkap.
</div>

</div>

<div class="input-row">

<textarea id="question" placeholder="Contoh: Toyota Hilux no start, DTC P0335, rpm scanner 0..."></textarea>

<button onclick="askAI()">
Kirim
</button>

</div>

<p class="note">
Catatan: AI ini hanya diagnosa awal. Untuk keputusan repair/remap tetap perlu pengecekan teknisi.
</p>

</div>

</div>

<script>
function setQ(text){
document.getElementById('question').value = text;
}

function addMsg(text, cls){
const chat = document.getElementById('chat');
const div = document.createElement('div');
div.className = 'msg ' + cls;
div.textContent = text;
chat.appendChild(div);
chat.scrollTop = chat.scrollHeight;
}

async function askAI(){

const input = document.getElementById('question');
const question = input.value.trim();

if(!question) return;

addMsg(question, 'user');

input.value = '';

addMsg('Sedang menganalisa...', 'bot');

try{

const res = await fetch('/ai-ecu/ai_reply.php', {
method:'POST',
headers:{
'Content-Type':'application/json',
'Accept':'application/json'
},
body:JSON.stringify({question:question})
});

const text = await res.text();

let data;

try{
data = JSON.parse(text);
}catch(e){
document.querySelector('#chat .bot:last-child').textContent =
'Server bukan JSON. Response: ' + text.substring(0,500);
return;
}

document.querySelector('#chat .bot:last-child').textContent =
data.answer || 'Maaf, AI belum bisa menjawab saat ini.';

}catch(e){

document.querySelector('#chat .bot:last-child').textContent =
'Gagal terhubung ke server AI: ' + e.message;

}

}
</script>

</body>
</html>