let audioContext = null;

export function useAudioFeedback() {
    function playSuccessBeep() {
        playTone(1240, 0.08, 'triangle', 0.045);
    }

    function playErrorBuzz() {
        playTone(180, 0.18, 'sawtooth', 0.06);
    }

    function playTone(frequency, durationSeconds, type, volume) {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;

        if (!AudioContextClass) {
            return;
        }

        if (!audioContext) {
            audioContext = new AudioContextClass();
        }

        if (audioContext.state === 'suspended') {
            audioContext.resume().catch(() => {});
        }

        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        const startAt = audioContext.currentTime;
        const stopAt = startAt + durationSeconds;

        oscillator.type = type;
        oscillator.frequency.setValueAtTime(frequency, startAt);
        gainNode.gain.setValueAtTime(volume, startAt);
        gainNode.gain.exponentialRampToValueAtTime(0.0001, stopAt);

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        oscillator.start(startAt);
        oscillator.stop(stopAt);
    }

    return {
        playSuccessBeep,
        playErrorBuzz,
    };
}
