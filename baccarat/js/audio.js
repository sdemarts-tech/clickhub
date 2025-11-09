const audioElements = {
    chip: document.getElementById('audio-chip'),
    card: document.getElementById('audio-card'),
    win: document.getElementById('audio-win'),
    tie: document.getElementById('audio-tie'),
    music: document.getElementById('audio-music')
};

const state = {
    music: false,
    sfx: true
};

export function toggleMusic() {
    state.music = !state.music;
    if (state.music) {
        audioElements.music.volume = 0.35;
        audioElements.music.play().catch(() => {});
    } else {
        audioElements.music.pause();
    }
    return state.music;
}

export function toggleSFX() {
    state.sfx = !state.sfx;
    return state.sfx;
}

export function playSFX(key) {
    if (!state.sfx) return;
    const audio = audioElements[key];
    if (!audio) return;
    audio.currentTime = 0;
    audio.play().catch(() => {});
}
