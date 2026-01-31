import { ref, watch, onMounted } from 'vue';

const isDark = ref(false);

export function useDarkMode() {
    const toggleDarkMode = () => {
        isDark.value = !isDark.value;
        updateTheme();
    };

    const setDarkMode = (value) => {
        isDark.value = value;
        updateTheme();
    };

    const updateTheme = () => {
        if (isDark.value) {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        }
    };

    const initTheme = () => {
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
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('theme')) {
                isDark.value = e.matches;
                updateTheme();
            }
        });
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