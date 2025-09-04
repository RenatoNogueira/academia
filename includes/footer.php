    <footer class="bg-gray-100 py-2 mt-5 px-4 border-t border-gray-200 mt-auto dark:bg-gray-900 dark:text-gray-100">
        <div class="container mx-auto text-center text-gray-600 text-sm dark:bg-gray-900 dark:text-gray-100">
            Versão do sistema: 1.0.0
        </div>
    </footer>

    <script>
// Funções para controle do tema
function applyStoredTheme() {
    const storedTheme = localStorage.getItem('theme');
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (storedTheme === 'dark' || (!storedTheme && systemPrefersDark)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
}

function toggleTheme() {
    const html = document.documentElement;
    html.classList.toggle('dark');

    const isDark = html.classList.contains('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

// Aplicar tema ao carregar
document.addEventListener('DOMContentLoaded', () => {
    applyStoredTheme();

    // Configurar botão de alternância
    document.getElementById('themeToggle').addEventListener('click', toggleTheme);

    // Atualizar gráficos para dark mode
    updateChartsForDarkMode();
});

// Atualizar estilos dos gráficos para dark mode
function updateChartsForDarkMode() {
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#f3f4f6' : '#111827';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

    // Atualizar gráfico de evolução de peso
    evolucaoPesoChart.options.scales.x.grid.color = gridColor;
    evolucaoPesoChart.options.scales.y.grid.color = gridColor;
    evolucaoPesoChart.options.scales.x.ticks.color = textColor;
    evolucaoPesoChart.options.scales.y.ticks.color = textColor;
    evolucaoPesoChart.options.plugins.legend.labels.color = textColor;
    evolucaoPesoChart.update();

    // Atualizar gráfico de IMC
    imcChart.options.plugins.legend.labels.color = textColor;
    imcChart.update();
}

// Observar mudanças de tema para atualizar gráficos
const observer = new MutationObserver(() => {
    updateChartsForDarkMode();
});
observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class']
});
    </script>


    <script>
const passwordInput = document.getElementById('password');
const togglePassword = document.getElementById('togglePassword');
const eyeIcon = document.getElementById('eyeIcon');

passwordInput.addEventListener('input', function() {
    // Mostra/oculta o ícone baseado no conteúdo do input
    togglePassword.style.display = this.value.length > 0 ? 'flex' : 'none';
});

togglePassword.addEventListener('click', function() {
    // Alterna entre mostrar e ocultar a senha
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);

    // Alterna o ícone do olho
    eyeIcon.classList.toggle('fa-eye-slash');
    eyeIcon.classList.toggle('fa-eye');
});
    </script>


    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Função para fechar alertas
    function autoHideAlert(alertElement) {
        setTimeout(function() {
            if (alertElement) {
                alertElement.style.opacity = '0';

                // Remove completamente o elemento após a transição
                setTimeout(function() {
                    alertElement.remove();
                }, 500);
            }
        }, 5000); // 5 segundos antes de iniciar o fade
    }

    // Configurar fechamento automático
    const errorAlert = document.getElementById('errorAlert');
    const successAlert = document.getElementById('successAlert');

    if (errorAlert) autoHideAlert(errorAlert);
    if (successAlert) autoHideAlert(successAlert);
});
    </script>