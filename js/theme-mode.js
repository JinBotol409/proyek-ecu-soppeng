// Load saat halaman dibuka
if(localStorage.getItem("theme") === "light"){
    document.body.classList.add("light-mode");
}

// Tombol ganti mode
document.addEventListener("DOMContentLoaded", function(){

    const btn = document.getElementById("themeToggle");

    if(!btn) return;

    if(localStorage.getItem("theme") === "light"){
        btn.innerHTML = "☀️";
    }

    btn.addEventListener("click", function(){

        document.body.classList.toggle("light-mode");

        if(document.body.classList.contains("light-mode")){
            localStorage.setItem("theme","light");
            btn.innerHTML = "☀️";
        }else{
            localStorage.setItem("theme","dark");
            btn.innerHTML = "🌙";
        }

    });

});
