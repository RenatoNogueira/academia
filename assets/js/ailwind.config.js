module.exports = {
    darkMode: 'class',
    content: [
        './src/**/*.{html,js,ts,jsx,tsx}',
    ],
    theme: {
        extend: {
            colors: {
                // Cores personalizadas para light/dark mode
                background: {
                    light: '#ffffff',
                    dark: '#1a202c',
                },
                text: {
                    light: '#2d3748',
                    dark: '#f7fafc',
                },
            },
        },
    },
    variants: {
        extend: {
            backgroundColor: ['dark'],
            textColor: ['dark'],
            borderColor: ['dark'],
            // Adicione outras variantes conforme necessário
        },
    },
    plugins: [],
}