import { reactive, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

// Estado global reactivo para el temporizador
const state = reactive({
    isModalOpen: false,
    isRunning: false,
    isPaused: false,
    isWorkSession: true,
    secondsRemaining: 25 * 60,
    settings: {
        work_minutes: 25,
        break_minutes: 5,
    },
    originalTitle: document.title,
    countdown: null,
    audioContext: null,
});

export function usePomodoro() {

    // --- COMPUTED PROPERTIES ---
    const displayTime = computed(() => {
        const minutes = Math.floor(state.secondsRemaining / 60);
        const seconds = state.secondsRemaining % 60;
        return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    });

    // --- API CALLS ---
    // NOTA: Se ha corregido la ruta de la API. Se eliminó el prefijo '/api'.
    // Si tus rutas están en 'routes/api.php', deberías volver a añadirlo.
    // La ruta correcta depende de dónde hayas definido las rutas en Laravel.
    const fetchSettings = async () => {
        try {
            const response = await axios.get('/pomodoro/settings');
            state.settings.work_minutes = response.data.work_minutes;
            state.settings.break_minutes = response.data.break_minutes;
            resetTimer(); // Reset timer with fetched settings
        } catch (error) {
            console.error("Error fetching pomodoro settings:", error);
            // Puedes agregar una notificación al usuario aquí si lo deseas.
        }
    };

    const saveSettings = async () => {
        try {
            await axios.post('/pomodoro/settings', state.settings);
        } catch (error) {
            console.error("Error saving pomodoro settings:", error);
        }
    };

    const pauseTasks = async () => {
        try {
            await axios.post('/pomodoro/pause-tasks');
            console.log('User tasks paused.');
        } catch (error) {
            console.error('Failed to pause tasks:', error);
        }
    };
    
    const resumeTasks = async () => {
        try {
            await axios.post('/pomodoro/resume-tasks');
            console.log('User tasks resumed.');
        } catch (error) {
            console.error('Failed to resume tasks:', error);
        }
    };


    // --- AUDIO & NOTIFICATIONS ---
    // Sonido de alarma mejorado: más largo y notorio.
    const playAlarm = () => {
        try {
            state.audioContext = state.audioContext || new(window.AudioContext || window.webkitAudioContext)();
            const now = state.audioContext.currentTime;

            // Genera una secuencia de 4 pitidos para que sea más fácil de escuchar
            for (let i = 0; i < 4; i++) {
                const oscillator = state.audioContext.createOscillator();
                const gainNode = state.audioContext.createGain();
                oscillator.connect(gainNode);
                gainNode.connect(state.audioContext.destination);

                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(880, now + i * 0.5); // Nota A5

                gainNode.gain.setValueAtTime(1, now + i * 0.5);
                gainNode.gain.exponentialRampToValueAtTime(0.00001, now + i * 0.5 + 0.3);

                oscillator.start(now + i * 0.5);
                oscillator.stop(now + i * 0.5 + 0.3);
            }
        } catch (e) {
            console.error("Could not play alarm sound.", e);
        }
    };

    const showNotification = (body) => {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('Pomodoro Timer', { body });
        }
    };

    // --- BROWSER EVENT HANDLING ---
    // Muestra una alerta si se intenta recargar la página con el temporizador activo.
    const beforeUnloadListener = (event) => {
        if (state.isRunning && !state.isPaused) {
            event.preventDefault();
            // Estándar para la mayoría de los navegadores
            event.returnValue = 'El temporizador está en marcha. ¿Seguro que quieres salir?';
            return 'El temporizador está en marcha. ¿Seguro que quieres salir?';
        }
    };

    onMounted(() => {
        if ('Notification' in window && Notification.permission !== 'denied') {
            Notification.requestPermission();
        }
        fetchSettings();
        window.addEventListener('beforeunload', beforeUnloadListener);
    });

    onUnmounted(() => {
        window.removeEventListener('beforeunload', beforeUnloadListener);
        clearInterval(state.countdown); // Limpia el intervalo si el componente se desmonta
    });


    // --- TIMER LOGIC ---
    const startTimer = () => {
        if (state.isRunning && !state.isPaused) return;

        state.isRunning = true;
        state.isPaused = false;

        // Usamos un cálculo de tiempo final para mayor precisión
        const endTime = Date.now() + state.secondsRemaining * 1000;

        clearInterval(state.countdown);
        state.countdown = setInterval(() => {
            const secondsLeft = Math.round((endTime - Date.now()) / 1000);

            if (secondsLeft < 0) {
                clearInterval(state.countdown);
                handleSessionEnd();
                return;
            }
            state.secondsRemaining = secondsLeft;
        }, 1000);
    };
    
    const pauseTimer = () => {
        state.isPaused = true;
        clearInterval(state.countdown);
    };

    const resetTimer = (isFullReset = true) => {
        clearInterval(state.countdown);
        state.isRunning = false;
        state.isPaused = false;
        if (isFullReset) {
            state.isWorkSession = true;
        }
        state.secondsRemaining = (state.isWorkSession ? state.settings.work_minutes : state.settings.break_minutes) * 60;
        document.title = state.originalTitle;
    };
    
    // Lógica de fin de sesión actualizada para iniciar descansos automáticamente.
    const handleSessionEnd = () => {
        playAlarm();
        if (state.isWorkSession) {
            showNotification('¡Tiempo de enfoque terminado! Toma un descanso.');
            pauseTasks();
            state.isWorkSession = false;
            state.secondsRemaining = state.settings.break_minutes * 60;
            // El descanso inicia automáticamente
            startTimer();
        } else {
            // Después del descanso, inicia automáticamente la siguiente sesión de trabajo
            showNotification('El descanso ha terminado. ¡A enfocarse de nuevo!');
            resumeTasks();
            state.isWorkSession = true;
            state.secondsRemaining = state.settings.work_minutes * 60;
            startTimer();
        }
    };
    
    // --- MODAL CONTROL ---
    const toggleModal = (value = !state.isModalOpen) => {
        state.isModalOpen = value;
        if(value) {
            fetchSettings();
        }
    };

    return {
        state,
        displayTime,
        startTimer,
        pauseTimer,
        resetTimer,
        toggleModal,
        saveSettings,
    };
}
