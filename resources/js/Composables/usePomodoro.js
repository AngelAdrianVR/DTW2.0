import { reactive, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

// Estado global reactivo para el temporizador
const state = reactive({
    isModalOpen: false,
    isRunning: false,
    isPaused: false,
    isWorkSession: true,
    secondsRemaining: 25 * 60,
    completedSessions: 0,
    settings: {
        work_minutes: 25,
        break_minutes: 5,
        long_break_minutes: 15,
        sessions_before_long_break: 4
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
    const fetchSettings = async () => {
        try {
            const response = await axios.get('/pomodoro/settings');
            const { data } = response;
            state.settings.work_minutes = data.work_minutes;
            state.settings.break_minutes = data.break_minutes;
            state.settings.long_break_minutes = data.long_break_minutes;
            state.settings.sessions_before_long_break = data.sessions_before_long_break;
            
            if (!state.isRunning) {
                resetTimer();
            }
        } catch (error) {
            console.error("Error fetching pomodoro settings:", error);
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
            console.log('User tasks paused and time logged.');
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

    /**
     * NUEVO: Registra una sesión completada en la base de datos.
     */
    const logSession = async () => {
        try {
            const sessionType = state.isWorkSession ? 'work' : 'break';
            await axios.post('/pomodoro/log-session', { type: sessionType });
            console.log(`Session of type '${sessionType}' logged successfully.`);
        } catch (error) {
            console.error('Failed to log pomodoro session:', error);
        }
    };

    // --- AUDIO & NOTIFICATIONS ---
    const playAlarm = () => {
        try {
            state.audioContext = state.audioContext || new(window.AudioContext || window.webkitAudioContext)();
            const now = state.audioContext.currentTime;

            for (let i = 0; i < 4; i++) {
                const oscillator = state.audioContext.createOscillator();
                const gainNode = state.audioContext.createGain();
                oscillator.connect(gainNode);
                gainNode.connect(state.audioContext.destination);

                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(880, now + i * 0.5);

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
    const beforeUnloadListener = (event) => {
        if (state.isRunning && !state.isPaused) {
            event.preventDefault();
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
        clearInterval(state.countdown);
    });


    // --- TIMER LOGIC ---
    const startTimer = () => {
        if (state.isRunning && !state.isPaused) return;

        // MODIFICADO: Cierra el modal automáticamente al iniciar el timer.
        state.isModalOpen = false;

        state.isRunning = true;
        state.isPaused = false;
        
        if (state.isWorkSession) {
            resumeTasks();
        }

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
        if (state.isWorkSession) {
            pauseTasks();
        }
    };

    const resetTimer = (isFullReset = true) => {
        clearInterval(state.countdown);
        state.isRunning = false;
        state.isPaused = false;
        if (isFullReset) {
            state.isWorkSession = true;
            state.completedSessions = 0;
        }
        state.secondsRemaining = (state.isWorkSession ? state.settings.work_minutes : state.settings.break_minutes) * 60;
        document.title = state.originalTitle;
    };
    
    const handleSessionEnd = () => {
        playAlarm();

        // NUEVO: Registra la sesión que acaba de terminar.
        logSession();
        
        if (state.isWorkSession) {
            state.completedSessions++;
            pauseTasks(); 
            
            if (state.completedSessions > 0 && state.completedSessions % state.settings.sessions_before_long_break === 0) {
                showNotification(`¡Gran trabajo! Tómate un descanso largo de ${state.settings.long_break_minutes} minutos.`);
                state.isWorkSession = false;
                state.secondsRemaining = state.settings.long_break_minutes * 60;
            } else {
                showNotification(`¡Tiempo de enfoque terminado! Toma un descanso corto de ${state.settings.break_minutes} minutos.`);
                state.isWorkSession = false;
                state.secondsRemaining = state.settings.break_minutes * 60;
            }
        } else {
            showNotification('El descanso ha terminado. ¡A enfocarse de nuevo!');
            state.isWorkSession = true;
            state.secondsRemaining = state.settings.work_minutes * 60;
        }

        // MODIFICADO: Reinicia el estado 'isRunning' para permitir que el siguiente timer inicie automáticamente.
        state.isRunning = false;
        
        startTimer();
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
