import { ref, onMounted } from 'vue';

const isDark = ref(false);
let listeningToSystem = false;

const updateTheme = () => {
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

/**
 * Apply the saved theme (or the system preference). Called once when the app starts,
 * so the theme survives reloads on every page.
 */
export const initTheme = () => {
    // Check localStorage first
    const savedTheme = localStorage.getItem('theme');

    if (savedTheme) {
        isDark.value = savedTheme === 'dark';
    } else {
        // Fall back to system preference
        isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    updateTheme();

    // Listen for system theme changes
    if (!listeningToSystem) {
        listeningToSystem = true;
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('theme')) {
                isDark.value = e.matches;
                updateTheme();
            }
        });
    }
};

export function useDarkMode() {
    const toggleDarkMode = () => {
        isDark.value = !isDark.value;
        updateTheme();
    };

    const setDarkMode = (value) => {
        isDark.value = value;
        updateTheme();
    };

    onMounted(() => {
        initTheme();
    });

    return {
        isDark,
        toggleDarkMode,
        setDarkMode,
        initTheme
    };
}
