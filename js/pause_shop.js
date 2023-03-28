function time_pause_enabled_change_color() {
    let time_pause_enabled = document.getElementById("time-pause-enabled").checked;
    let inputs = document.getElementsByClassName("time-pause-input");
    
    if (time_pause_enabled) {
        for (let i = 0; i < inputs.length; i++) {
            inputs[i].style.backgroundColor = "var(--wc-primary-text)";
        }
    } else {
        for (let i = 0; i < inputs.length; i++) {
            inputs[i].style.backgroundColor = "var(--wc-secondary)";
        }
    }
}

function init() {
    let time_pause_enabled_checkbox = document.getElementById("time-pause-enabled");

    time_pause_enabled_change_color();
    time_pause_enabled_checkbox.addEventListener("change", time_pause_enabled_change_color);
}

init();
alert('loaded');