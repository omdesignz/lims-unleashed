(function() {
    // Define the cookieConsent function globally
    window.cookieConsent = function() {
        return {
            visible: true,
            openCustomizationSlideOver: false,
            showNotification: false,
            showCookiePolicy: false,
            showPrivacyPolicy: false,
            message: '',
            preferences: {
                analytics: false,
                marketing: false,
                functional: false
            },
            rejectAll() {
                this.visible = false;
                this.openCustomizationSlideOver = false;
                this.message = 'Lembre-se que a desativação dos cookies pode afetar a sua experiência no site.';
                this.showNotification = true;
            },
            acceptAll() {
                this.preferences.analytics = true;
                this.preferences.marketing = true;
                this.preferences.functional = true;
                this.savePreferences();
            },
            savePreferences() {
                localStorage.setItem('cookiePreferences', JSON.stringify(this.preferences));
                this.visible = false;
                this.openCustomizationSlideOver = false;
                this.message = 'Obrigado. Utilizamos diferentes tipos de cookies para otimizar a sua experiência no nosso site.';
                this.showNotification = true;

            },
            init() {
                let savedPreferences = localStorage.getItem('cookiePreferences');
                if (savedPreferences) {
                    this.preferences = JSON.parse(savedPreferences);
                    this.visible = false;
                    this.openCustomizationSlideOver = false;
                    this.showNotification = false;
                    this.showCookiePolicy = false;
                    this.showPrivacyPolicy = false;
                }
            }
        }
    };

    // Function to create and initialize the widget
    function initializeWidget() {
        // Create the shadow DOM container
        var shadowContainer = document.createElement('div');
        var shadowRoot = shadowContainer.attachShadow({ mode: 'open' });

        // Inject Tailwind CSS into the shadow DOM
        var tailwindLink = document.createElement('link');
        tailwindLink.href = 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css';
        tailwindLink.rel = 'stylesheet';
        shadowRoot.appendChild(tailwindLink);

        // Inject custom styles for rounded checkboxes
        var customStyles = document.createElement('style');
        customStyles.textContent = `
            input[type="checkbox"] {
                appearance: none;
                -webkit-appearance: none;
                width: 1rem;
                height: 1rem;
                border-radius: 9999px;
                border: 2px solid #4a5568;
                background-color: white;
                display: inline-block;
                position: relative;
                transition: background-color 0.2s, border-color 0.2s;
            }
            input[type="checkbox"]:checked {
                background-color: #3b82f6;
                border-color: #3b82f6;
            }
            input[type="checkbox"]:checked::after {
                content: '';
                display: block;
                width: 0.5rem;
                height: 0.5rem;
                border-radius: 9999px;
                background: white;
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
            }
        `;
        shadowRoot.appendChild(customStyles);

        // Create the widget container inside the shadow DOM
        var container = document.createElement('div');
        container.innerHTML = `
            <div x-data="cookieConsent()" x-init="init()" class="fixed bottom-0 inset-x-0 p-4 bg-white shadow-lg border-t border-gray-200">

            <div x-show="showNotification" class="pointer-events-none fixed inset-x-0 bottom-0 sm:flex sm:justify-center sm:px-6 sm:pb-5 lg:px-8"
                    x-transition:enter="transform transition ease-in-out duration-500 sm:duration-300"
                    x-transition:enter-start="translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transform transition ease-in-out duration-500 sm:duration-300"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="translate-x-full"
            >
                <div class="pointer-events-auto flex items-center justify-between gap-x-6 bg-gradient-to-r from-yellow-200 to-yellow-400 px-6 py-2.5 sm:rounded-full sm:py-3 sm:pl-4 sm:pr-3.5">
                    <p class="text-sm leading-6 text-gray-900">
                    <a href="#">
                        <strong class="font-semibold" x-text="message"></strong><span aria-hidden="true"></span>
                    </a>
                    </p>
                    <button @click="showNotification = false" class="-m-1.5 flex-none p-1.5">
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-5 w-5 text-gray-900" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                    </svg>
                    </button>
                </div>
            </div>

            <div class="relative z-10" aria-labelledby="slide-over-title" role="dialog" aria-modal="true" x-show="openCustomizationSlideOver">
            <!-- Background backdrop, show/hide based on slide-over state. -->
            <div class="fixed inset-0"></div>

            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <!--
                    Slide-over panel, show/hide based on slide-over state.

                    Entering: "transform transition ease-in-out duration-500 sm:duration-700"
                        From: "translate-x-full"
                        To: "translate-x-0"
                    Leaving: "transform transition ease-in-out duration-500 sm:duration-700"
                        From: "translate-x-0"
                        To: "translate-x-full"
                    -->
                    <div class="pointer-events-auto w-screen max-w-md"
                    x-transition:enter="transform transition ease-in-out duration-700 sm:duration-700"
                    x-transition:enter-start="translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transform transition ease-in-out duration-700 sm:duration-700"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="translate-x-full"
                    >
                    <div class="flex h-full flex-col overflow-y-scroll bg-gradient-to-r from-yellow-200 to-yellow-400 py-6 shadow-xl rounded-bl-full">
                        <div class="px-4 sm:px-6">
                        <div class="flex items-start justify-between">
                            <h2 class="text-base font-semibold leading-6 text-gray-900" id="slide-over-title">Preferência de Cookies</h2>
                            <div class="ml-3 flex h-7 items-center">
                            <button @click="openCustomizationSlideOver = false" class="relative rounded-md bg-transparent text-gray-900 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                <span class="absolute -inset-2.5"></span>
                                <span class="sr-only">Close panel</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            </div>
                        </div>
                        </div>
                        <div class="relative mt-6 flex-1 px-4 sm:px-6">
                        <!-- Your content -->
                        <fieldset>
                            <legend class="sr-only">Notifications</legend>
                                <div class="space-y-5">
                                    <div class="relative flex items-start">
                                        <div class="flex h-6 items-center">
                                            <input x-model="preferences.functional" id="functional" aria-describedby="functional-description" name="functional" type="checkbox" class="h-4 w-4 form-checkbox rounded-full border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                        </div>
                                        <div class="ml-3 text-sm leading-6">
                                            <label for="functional" class="font-medium text-gray-900">Cookies Funcionais</label>
                                            <p id="functional-description" class="text-gray-500">Alguns cookies são essenciais para acessar a áreas específicas do nosso site. Permitem a navegação no site e a utilização das suas aplicações, tal como acessar áreas seguras do site através de login. Sem estes cookies, os serviços que o exijam não podem ser prestados.</p>
                                        </div>
                                    </div>
                                    <div class="relative flex items-start">
                                        <div class="flex h-6 items-center">
                                            <input x-model="preferences.analytics" id="analytics" aria-describedby="analytics-description" name="analytics" type="checkbox" class="h-4 w-4 form-checkbox rounded-full border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                        </div>
                                        <div class="ml-3 text-sm leading-6">
                                            <label for="analytics" class="font-medium text-gray-900">Cookies Analíticos</label>
                                            <p id="analytics-description" class="text-gray-500">---</p>
                                        </div>
                                    </div>

                                    <button @click="savePreferences" class="bg-yellow-400 hover:bg-gray-800 hover:text-white text-gray-800 w-full px-4 py-2 rounded-full">Guardar Preferências</button>


                                </div>
                            </fieldset>
                        </div>
                    </div>
                    </div>
                </div>
                </div>
            </div>
            </div>

            <div x-show="visible" class="pointer-events-none fixed inset-x-0 bottom-0 px-6 pb-6">
            <div class="pointer-events-auto max-w-xl rounded-xl bg-gray-700 p-6 shadow-lg">
            <p class="max-w-4xl text-sm leading-6 text-white text-justify">
                Usamos cookies essenciais para fazer nosso site funcionar. Com o seu consentimento, também poderemos utilizar cookies não essenciais para melhorar a experiência do utilizador e analisar o tráfego do site. Ao clicar em “Aceitar”, você concorda com o uso de cookies em nosso site conforme descrito em nossa <a @click="showCookiePolicy = true" href="#" class="font-semibold text-yellow-400">Política de Cookies</a> (poderá também verificar a nossa <a @click="showPrivacyPolicy = true" href="#" class="font-semibold text-yellow-400">Política de Privacidade</a>). Você pode alterar suas configurações de cookies a qualquer momento clicando em “Preferências”.
            </p>
            <hr />
                <div class="flex flex-none items-center gap-x-5 mt-2">
                    <span class="isolate inline-flex rounded-full">
                        <button @click="openCustomizationSlideOver = true" class="relative inline-flex items-center rounded-l-full bg-yellow-400 px-3 py-2 text-sm font-semibold text-gray-900 hover:bg-yellow-300 focus:z-10">Preferências</button>
                        <button @click="acceptAll" class="relative -ml-px inline-flex items-center bg-yellow-400 px-3 py-2 text-sm font-semibold text-gray-900 hover:bg-yellow-300 focus:z-10">Aceitar</button>
                        <button @click="rejectAll" class="relative -ml-px inline-flex items-center rounded-r-full bg-yellow-400 px-3 py-2 text-sm font-semibold text-gray-900 hover:bg-yellow-300 focus:z-10">Rejeitar</button>
                    </span>
                </div>
            </div>
            </div>

            

            <div x-show="showCookiePolicy" class="relative z-10" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
            <!-- Background backdrop, show/hide based on slide-over state. -->
            <div class="fixed inset-0"></div>

            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10 sm:pl-16">
                    
                    <div class="pointer-events-auto w-screen max-w-2xl"
                        x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                        x-transition:enter-start="translate-x-full"
                        x-transition:enter-end="translate-x-0"
                        x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                        x-transition:leave-start="translate-x-0"
                        x-transition:leave-end="translate-x-full"
                    >
                    <form class="flex h-full flex-col overflow-y-scroll bg-gradient-to-r from-yellow-200 to-yellow-400 shadow-xl">
                        <div class="flex-1">
                        <!-- Header -->
                        <div class="bg-gray-700 px-4 py-6 sm:px-6 rounded-br-full">
                            <div class="flex items-start justify-between space-x-3">
                            <div class="space-y-1">
                                <h2 class="text-base font-semibold leading-6 text-white" id="slide-over-title">Política de Cookies</h2>
                                <p class="text-sm text-white text-justify"></p>
                            </div>
                            <div class="flex h-7 items-center">
                                <button @click="showCookiePolicy = false" type="button" class="relative text-white hover:text-gray-500">
                                <span class="absolute -inset-2.5"></span>
                                <span class="sr-only">Close panel</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                </button>
                            </div>
                            </div>
                        </div>

                        <!-- Divider container -->

                        <div class="space-y-6 py-2 sm:space-y-0 sm:divide-y sm:divide-gray-200 sm:py-0 sm:px-6">
                        <div class="">
                        <div class="mx-auto max-w-7xl py-2 sm:py-16 lg:py-4">
                            <div class="mx-auto max-w-4xl">
                            <h2 class="text-2xl font-bold leading-10 tracking-tight text-gray-900"></h2>
                            <dl class="mt-4 space-y-6 divide-y divide-gray-900/10">
                                <div x-data="{ open: false}" class="pt-6">
                                <dt>
                                    <button @click="open = !open" class="flex w-full items-start justify-between text-left text-gray-900" aria-controls="faq-0" aria-expanded="false">
                                    <span class="text-base font-semibold leading-7">O que são cookies?</span>
                                    <span class="ml-6 flex h-7 items-center">
                                        
                                        <svg x-show="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                                        </svg>
                                        
                                        <svg x-show="open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                                        </svg>
                                    </span>
                                    </button>
                                </dt>
                                <dd x-show="open" class="mt-2 pr-12" id="faq-0">
                                    <p class="text-base leading-7 text-gray-600 text-justify">
                                    Os cookies são pequenos arquivos de texto que um site, quando visitado, coloca no computador do usuário ou no seu dispositivo móvel, através do navegador de internet (browser). A colocação de cookies ajudará o site a reconhecer o seu dispositivo numa próximo visita. Usamos o termo cookies nesta política para referir todos os arquivos que recolhem informações desta forma.
                                    </p>
                                </dd>
                                </div>

                                <div x-data="{ open1: false}" class="pt-6">
                                <dt>
                                    <button @click="open1 = !open1" class="flex w-full items-start justify-between text-left text-gray-900" aria-controls="faq-0" aria-expanded="false">
                                    <span class="text-base font-semibold leading-7">Para que servem os cookies?</span>
                                    <span class="ml-6 flex h-7 items-center">
                                        
                                        <svg x-show="!open1" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                                        </svg>
                                        
                                        <svg x-show="open1" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                                        </svg>
                                    </span>
                                    </button>
                                </dt>
                                <dd x-show="open1" class="mt-2 pr-12" id="faq-0">
                                    <p class="text-base leading-7 text-gray-600 text-justify">Os cookies são usados para ajudar a determinar a utilidade, interesse e o número de utilizações dos sites, permitindo uma navegação mais rápida e eficiente e eliminando a necessidade de introduzir repetidamente as mesmas informações.</p>
                                </dd>
                                </div>

                                <div x-data="{ open2: false}" class="pt-6">
                                <dt>
                                    <button @click="open2 = !open2" class="flex w-full items-start justify-between text-left text-gray-900" aria-controls="faq-0" aria-expanded="false">
                                    <span class="text-base font-semibold leading-7">Que tipos de cookies utilizamos?</span>
                                    <span class="ml-6 flex h-7 items-center">
                                        
                                        <svg x-show="!open2" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                                        </svg>
                                        
                                        <svg x-show="open2" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                                        </svg>
                                    </span>
                                    </button>
                                </dt>
                                <dd x-show="open2" class="mt-2 pr-12" id="faq-0">
                                    <p class="text-base leading-7 text-gray-600 text-justify">
                                    <dl class="space-y-10">
                                    <div>
                                      <dt class="text-base font-semibold leading-7 text-gray-900">Cookies Essenciais</dt>
                                      <dd class="mt-2 text-base leading-7 text-gray-600 text-justify">
                                        Alguns cookies são essenciais para acessar a áreas específicas do nosso site. Permitem a navegação no site e a utilização das suas aplicações, tal como acessar áreas seguras do site através de login. Sem estes cookies, os serviços que o exijam não podem ser prestados.
                                      </dd>
                                    </div>

                                    <div>
                                      <dt class="text-base font-semibold leading-7 text-gray-900">Cookies Analíticos</dt>
                                      <dd class="mt-2 text-base leading-7 text-gray-600 text-justify">
                                      Utilizamos estes cookies para analisar a forma como os usuários usam o site e monitorar a performance deste. Isto permite-nos fornecer uma experiência de alta qualidade ao personalizar a nossa oferta e rapidamente identificar e corrigir quaisquer problemas que surjam. Por exemplo, usamos cookies de desempenho para saber quais as páginas mais populares, qual o método de ligação entre páginas que é mais eficaz, ou para determinar a razão de algumas páginas estarem a receber mensagens de erro. Baseado na utilização do site, podemos também utilizar estes cookies para destacar artigos ou serviços do site que pensamos ser do interesse dos usuários. Estes cookies são utilizados apenas para efeitos de criação e análise estatística, sem nunca recolher informação de carácter pessoal.
                                      </dd>
                                    </div>

                                    <div>
                                      <dt class="text-base font-semibold leading-7 text-gray-900">Cookies de Funcionalidade</dt>
                                      <dd class="mt-2 text-base leading-7 text-gray-600 text-justify">
                                      Utilizamos cookies de funcionalidade para nos permitir relembrar as preferências do usuário. Por exemplo, os cookies evitam digitar o nome do utilizador cada vez que este acede ao site. Também usamos cookies de funcionalidade para fornecer serviços avançados ao usuário, como por exemplo efetuar comentários a um artigo. Em resumo, os cookies de funcionalidade guardam as preferências do usuário relativamente à utilização do site, de forma que não seja necessário voltar a configurar o site cada vez que o visita.
                                      </dd>
                                    </div>
                          
                                    <!-- More questions... -->
                                  </dl>
                                    </p>
                                </dd>
                                </div>
                
                                <!-- More questions... -->
                            </dl>
                            </div>
                        </div>
                        </div>
                        </div>
                        </div>

                        </div>
                    </form>
                    </div>
                </div>
                </div>
            </div>



            <div x-show="showPrivacyPolicy" class="relative z-10" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
            <!-- Background backdrop, show/hide based on slide-over state. -->
            <div class="fixed inset-0"></div>

            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10 sm:pl-16">
                    
                    <div class="pointer-events-auto w-screen max-w-2xl"
                        x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                        x-transition:enter-start="translate-x-full"
                        x-transition:enter-end="translate-x-0"
                        x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                        x-transition:leave-start="translate-x-0"
                        x-transition:leave-end="translate-x-full"
                    >
                    <form class="flex h-full flex-col overflow-y-scroll bg-gradient-to-r from-yellow-200 to-yellow-400 shadow-xl">
                        <div class="flex-1">
                        <!-- Header -->
                        <div class="bg-gray-700 px-4 py-6 sm:px-6 rounded-br-full">
                            <div class="flex items-start justify-between space-x-3">
                            <div class="space-y-1">
                                <h2 class="text-base font-semibold leading-6 text-white" id="slide-over-title">Política de Privacidade e Termo de Uso</h2>
                                <p class="text-sm text-white text-justify"></p>
                            </div>
                            <div class="flex h-7 items-center">
                                <button @click="showPrivacyPolicy = false" type="button" class="relative text-white hover:text-gray-500">
                                <span class="absolute -inset-2.5"></span>
                                <span class="sr-only">Close panel</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                </button>
                            </div>
                            </div>
                        </div>

                        <!-- Divider container -->

                        <div class="space-y-6 py-2 sm:space-y-0 sm:divide-y sm:divide-gray-200 sm:py-0 sm:px-6">
                        <div class="">
                        <p class="text-base leading-7 text-gray-600 text-justify mt-4">
                            O presente termo de uso e política de privacidade visa registar a manifestação livre, informada e inequívoca pela qual o Titular dos dados concorda com o tratamento de seus dados pessoais para finalidade específica, em conformidade com a Lei n.º 22/11 de 17 de Junho de 2011 – Lei da Proteção de Dados Pessoais (LPDP).
                        </p>
                        <div class="mx-auto max-w-7xl py-2 sm:py-16 lg:py-4">
                            <div class="mx-auto max-w-4xl">
                            <h2 class="text-2xl font-bold leading-10 tracking-tight text-gray-900"></h2>
                            <dl class="mt-4 space-y-6 divide-y divide-gray-900/10">
                                <div x-data="{ open: false}" class="pt-6">
                                <dt>
                                    <button @click="open = !open" class="flex w-full items-start justify-between text-left text-gray-900" aria-controls="faq-0" aria-expanded="false">
                                    <span class="text-base font-semibold leading-7">Objeto</span>
                                    <span class="ml-6 flex h-7 items-center">
                                        
                                        <svg x-show="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                                        </svg>
                                        
                                        <svg x-show="open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                                        </svg>
                                    </span>
                                    </button>
                                </dt>
                                <dd x-show="open" class="mt-2 pr-12" id="faq-0">
                                    <p class="text-base leading-7 text-gray-600 text-justify">
                                    O presente termo de uso e política de privacidade define à perspetiva geral da forma como o ISPTEC trata os dados pessoais de que dispõe, de acordo com o disposto na Lei n.º 22/11 de 17 de Junho, Lei da Proteção de Dados Pessoais (LPDP) e restante legislação aplicável em matéria de privacidade e proteção de dados. O ISPTEC realiza o tratamento dos dados pessoais dos usuários do website e do portal da instituição.
                                    </p>
                                </dd>
                                </div>

                                <div x-data="{ open1: false}" class="pt-6">
                                <dt>
                                    <button @click="open1 = !open1" class="flex w-full items-start justify-between text-left text-gray-900" aria-controls="faq-0" aria-expanded="false">
                                    <span class="text-base font-semibold leading-7">Âmbito e Aplicação</span>
                                    <span class="ml-6 flex h-7 items-center">
                                        
                                        <svg x-show="!open1" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                                        </svg>
                                        
                                        <svg x-show="open1" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                                        </svg>
                                    </span>
                                    </button>
                                </dt>
                                <dd x-show="open1" class="mt-2 pr-12" id="faq-0">
                                    <p class="text-base leading-7 text-gray-600 text-justify">O ISPTEC está empenhado em proteger a privacidade e os dados pessoais dos seus visitantes e utilizadores do website e do seu portal interno e, neste contexto, elaborou a presente <b>termo de uso e política de privacidade </b> com a finalidade de demonstrar o seu compromisso e respeito para com as regras de privacidade e de proteção de dados pessoais.</p>
                                </dd>
                                </div>

                                <div x-data="{ open2: false}" class="pt-6">
                                <dt>
                                    <button @click="open2 = !open2" class="flex w-full items-start justify-between text-left text-gray-900" aria-controls="faq-0" aria-expanded="false">
                                    <span class="text-base font-semibold leading-7">Definições</span>
                                    <span class="ml-6 flex h-7 items-center">
                                        
                                        <svg x-show="!open2" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                                        </svg>
                                        
                                        <svg x-show="open2" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                                        </svg>
                                    </span>
                                    </button>
                                </dt>
                                <dd x-show="open2" class="mt-2 pr-12" id="faq-0">
                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                    No presente termo de uso e política de privacidade para tratamento de dados pessoais, as palavras e expressões seguintes têm o significado que neste parágrafo se lhes atribui, salvo quando o contexto impuser diferente raciocínio, entende-se por:
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                    a)	<b>Consentimento</b> é a livre vontade de permitir que um determinado acto seja praticado de formas a alcançar uma determinada finalidade;
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                    b)	<b>Dados Pessoais</b> entende-se por quaisquer informações relacionadas com o Titular dos Dados e que o permitam identificar;
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                    c)	<b>Partes</b> significa a Contratante e a Contratada em conjunto e Parte significa qualquer delas individualmente;
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                    d)	<b>Titular dos Dados</b> qualquer pessoa singular identificada ou identificável a quem os Dados Pessoais se encontram associados. A pessoa singular pode ser identificável ou identificada, direta ou indiretamente, nomeadamente, através do nome, número de identificação civil ou fiscal, dados de localização, identificadores online (tais como endereços de IP e logs). A identificação também pode ser feita mediante fatores de identidade física, fisiológica, genética, mental, económica, cultural ou social. O utilizador do website, aplicações e plataformas digitais considera-se como Titular dos Dados em relação ao ISPTEC;
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                    e)	<b>Tratamento</b> qualquer operação efectuada no âmbito dos Dados Pessoais, através de meios automáticos ou não, tal como a recolha, gravação, organização, estruturação, armazenamento, adaptação ou alteração, recuperação, consulta, utilização, divulgação por transmissão, disseminação ou, alternativamente, disponibilização, harmonização ou associação, restrição, eliminação ou destruição. Também é considerado Tratamento de Dados Pessoais qualquer outra operação prevista nos termos da LPDP.
                                    </p>
        
                                </dd>
                                </div>


                                <div x-data="{ open3: false}" class="pt-6">
                                <dt>
                                    <button @click="open3 = !open3" class="flex w-full items-start justify-between text-left text-gray-900" aria-controls="faq-0" aria-expanded="false">
                                    <span class="text-base font-semibold leading-7">Dados Pessoais</span>
                                    <span class="ml-6 flex h-7 items-center">
                                        
                                        <svg x-show="!open3" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                                        </svg>
                                        
                                        <svg x-show="open3" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                                        </svg>
                                    </span>
                                    </button>
                                </dt>
                                <dd x-show="open3" class="mt-2 pr-12" id="faq-0">
                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                    O Titular autoriza o ISPTEC a realizar o tratamento, ou seja, a utilizar os seguintes dados pessoais, para os fins que serão relacionados na finalidade e tratamento dos seguintes dados:
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                    a)	<b>Endereço IP</b>;
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                    b)	<b>Localização</b>;
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                    c)	<b>Tipo de Dispositivo</b>;
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                    d)	<b>Horário de Acesso</b>.
                                    </p>
        
                                </dd>
                                </div>


                                <div x-data="{ open4: false}" class="pt-6">
                                <dt>
                                    <button @click="open4 = !open4" class="flex w-full items-start justify-between text-left text-gray-900" aria-controls="faq-0" aria-expanded="false">
                                    <span class="text-base font-semibold leading-7">Finalidade e Tratamento de Dados</span>
                                    <span class="ml-6 flex h-7 items-center">
                                        
                                        <svg x-show="!open4" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                                        </svg>
                                        
                                        <svg x-show="open4" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                                        </svg>
                                    </span>
                                    </button>
                                </dt>
                                <dd x-show="open4" class="mt-2 pr-12" id="faq-0">
                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        Os dados pessoais fornecidos pelo titular dos dados ou gerados face ao serviço prestado, serão processados e armazenados informaticamente, destinando-se a ser utilizados pelo ISPTEC com bases no artigo 6.º da LPDP.
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        O ISPTEC recolhe e processa os dados estritamente necessários, que são apenas solicitados quando relacionados com o propósito em causa, e para fins legítimos, tais como:
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        a)	Comunicar melhor com os titulares dos dados pessoais, para assuntos relevantes com a frequência necessária, segundo a caracterização dos seus dados e das suas preferências;
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        b)	Cumprir com propósitos empresarial, nomeadamente, dados estatísticos para melhoria do desempenho dos vários serviços prestados;
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        O ISPTEC recolhe e processa os dados estritamente necessários, que são apenas solicitados quando relacionados com o propósito em causa, e para fins legítimos, tais como:
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        <b>a) Portal online:</b> Inscrição online para o curso preparatório, inscrição para o exame de acesso e acesso ao portal do docente e estudante;
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        <b>b) Cumprimento de Obrigações Legais:</b> Inclui o tratamento de dados pessoais estritamente necessários para o cumprimento das suas obrigações legais, como a divulgação de dados na sequência de mandatos judiciais, colaboração com os reguladores e a defesa dos interesses legítimos do ISPTEC.
                                    </p>
        
                                </dd>
                                </div>


                                <div x-data="{ open5: false}" class="pt-6">
                                <dt>
                                    <button @click="open5 = !open5" class="flex w-full items-start justify-between text-left text-gray-900" aria-controls="faq-0" aria-expanded="false">
                                    <span class="text-base font-semibold leading-7">Partilha de Dados</span>
                                    <span class="ml-6 flex h-7 items-center">
                                        
                                        <svg x-show="!open5" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                                        </svg>
                                        
                                        <svg x-show="open5" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                                        </svg>
                                    </span>
                                    </button>
                                </dt>
                                <dd x-show="open5" class="mt-2 pr-12" id="faq-0">
                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                    1. O ISPTEC fica autorizado a compartilhar os dados pessoais do titular com outros agentes de tratamento de dados caso seja necessário, para as finalidades referidas na cláusula anterior, desde que, sejam respeitados os princípios da boa-fé, finalidades, adequação, necessidade, livre acesso, qualidade dos dados, transparência, segurança, prevenção, não discriminação e responsabilização.
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                    2. O Titular autoriza que o ISPTEC utilize os dados pessoais listados neste termo para as seguintes finalidades:
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        a) Permitir que o ISPTEC identifique e entre em contato com o titular, em razão do vínculo existente entre ambos;
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        b) Para cumprimento de obrigações decorrentes do vínculo existente;
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        c)Para cumprimento de obrigações impostas por órgãos de fiscalização;
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        d) Quando necessário, para executar contratos no qual seja parte o titular;
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        e) À petição do titular dos dados;
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        f) Permitir que o ISPTEC utilize esses dados para a divulgação em empresas que apoiam ou apadrinham o ISPTEC;
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        g) Para o exercício regular de direitos em processo judicial, administrativo ou arbitral;
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        h) Quando necessário para atender aos interesses legítimos do ISPTEC ou de terceiros, exceto no caso de prevalecerem direitos e liberdades fundamentais do titular que exijam a proteção dos dados pessoais;
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        i) Caso seja necessário o compartilhamento de dados com terceiros que não tenham sido relacionados nesse termo ou qualquer alteração contratual posterior, será ajustado novo termo de consentimento para este fim;
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        j) Em caso de alteração da partilha, que esteja em desacordo com o consentimento, o ISPTEC deverá comunicar o titular, que poderá revogar o consentimento, conforme previsto nesta cláusula.
                                    </p>
        
                                </dd>
                                </div>



                                <div x-data="{ open3: false}" class="pt-6">
                                <dt>
                                    <button @click="open3 = !open3" class="flex w-full items-start justify-between text-left text-gray-900" aria-controls="faq-0" aria-expanded="false">
                                    <span class="text-base font-semibold leading-7">Dados Pessoais</span>
                                    <span class="ml-6 flex h-7 items-center">
                                        
                                        <svg x-show="!open3" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                                        </svg>
                                        
                                        <svg x-show="open3" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                                        </svg>
                                    </span>
                                    </button>
                                </dt>
                                <dd x-show="open3" class="mt-2 pr-12" id="faq-0">
                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                    O Titular autoriza o ISPTEC a realizar o tratamento, ou seja, a utilizar os seguintes dados pessoais, para os fins que serão relacionados na finalidade e tratamento dos seguintes dados:
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                    a)	<b>Endereço IP</b>;
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                    b)	<b>Localização</b>;
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                    c)	<b>Tipo de Dispositivo</b>;
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                    d)	<b>Horário de Acesso</b>.
                                    </p>
        
                                </dd>
                                </div>


                                <div x-data="{ open6: false}" class="pt-6">
                                <dt>
                                    <button @click="open6 = !open6" class="flex w-full items-start justify-between text-left text-gray-900" aria-controls="faq-0" aria-expanded="false">
                                    <span class="text-base font-semibold leading-7">Links de Terceiros</span>
                                    <span class="ml-6 flex h-7 items-center">
                                        
                                        <svg x-show="!open6" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                                        </svg>
                                        
                                        <svg x-show="open6" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                                        </svg>
                                    </span>
                                    </button>
                                </dt>
                                <dd x-show="open6" class="mt-2 pr-12" id="faq-0">
                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        O ISPTEC disponibiliza links para outras páginas relacionadas a várias actividades de outras institucionais e de parceiros.
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        No caso dos links acima ou outros existentes que não pertençam ao ISPTEC nem são controlados por nós, o ISPTEC não se responsabiliza pelo seu conteúdo sendo por sua conta em risco a sua navegação e utilização.
                                    </p>
        
                                </dd>
                                </div>


                                <div x-data="{ open7: false}" class="pt-6">
                                <dt>
                                    <button @click="open7 = !open7" class="flex w-full items-start justify-between text-left text-gray-900" aria-controls="faq-0" aria-expanded="false">
                                    <span class="text-base font-semibold leading-7">Segurança dos Dados</span>
                                    <span class="ml-6 flex h-7 items-center">
                                        
                                        <svg x-show="!open7" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                                        </svg>
                                        
                                        <svg x-show="open7" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                                        </svg>
                                    </span>
                                    </button>
                                </dt>
                                <dd x-show="open7" class="mt-2 pr-12" id="faq-0">
                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        O ISPTEC responsabiliza-se pela manutenção de medidas de segurança, técnicas e administrativas aptas a proteger os dados pessoais de acessos não autorizados e de situações acidentais ou ilícitas de destruição, perda, alteração, comunicação ou qualquer forma de tratamento inadequado.
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        Em conformidade ao art. 30º e 31º da Lei n.º 22/11 de 17 de Junho de 2011 – Lei da Proteção de Dados Pessoais (LPDP), o ISPTEC comunicará ao titular e à Agência de Protecção de Dados (APD) sempre que ocorrer incidente de segurança que possam acarretar riscos ou dano relevante ao titular.
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        Os nossos sistemas de proteção atendem a todos os requisitos trazidos na Lei n.º 22/11 de 17 de Junho de 2011, respeitando as condutas de boa prática, as medidas de segurança técnicas e administrativas para proteção dos dados coletados.
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        Temos muito cuidado e preocupação em proporcionar segurança aos dados coletados dos nossos usuários, por esse motivo utilizamos ferramentas específicas para garantir a privacidade dos dados que tratamos.
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        As técnicas utilizadas para assegurar a segurança dos dados coletados por nosso website e portal online são todas adequadas e aptas a gerar a proteção integral dos dados dos usuários de qualquer possível situação onde possa ser obtido os dados, compartilhados, publicados, destruídos e alterados sem autorização e de maneira irregular.
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        Adotamos medidas específicas para cada espécie de dados sendo o modo de segurança direcionado especificadamente para o dado que foi coletado, de acordo com o contexto da sua coleta e sua finalidade.
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        Utilizamos proteção em nossos computadores por meio da criptografia de dados e/ ou firewall.
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        O acesso físico aos nossos arquivos só acontece quando é necessário que algum de nossos funcionários tenham acesso a eles para desempenhar sua função profissional.
                                    </p>

                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        É importante informar que tomamos todos os cuidados para proteger os dados de nossos usuários, mas ainda assim, há algumas situações que acontecem onde há o vazamento de dados. O nosso website não é responsável por qualquer incidente com dados pessoais que aconteça por situação em que não temos o controle, como a culpa exclusiva do usuário, ou ataques de hackers.
                                    </p>


                                    <p class="text-base leading-7 text-gray-600 text-justify mb-2">
                                        Qualquer incidente que cause danos aos dados pessoais dos usuários, informaremos as autoridades responsáveis, conforme a Lei n.º 22/11 de 17 de Junho de 2011 – Lei da Proteção de Dados Pessoais (LPDP).
                                    </p>
        
                                </dd>
                                </div>

                
                                <!-- More questions... -->
                            </dl>
                            </div>
                        </div>
                        </div>
                        </div>
                        </div>

                        </div>
                    </form>
                    </div>
                </div>
                </div>
            </div>
            </div>

            
            </div>
        `;
        shadowRoot.appendChild(container);

        // Append the shadow container to the body
        document.body.appendChild(shadowContainer);

        // Initialize Alpine.js within the shadow DOM
        container.addEventListener('alpine:init', () => {
            Alpine.data('cookieConsent', window.cookieConsent);
        });

        // Force Alpine.js to re-scan the DOM within the shadow DOM
        Alpine.initTree(container);
    }

    // Inject Alpine.js into the main document
    var alpineScript = document.createElement('script');
    alpineScript.src = 'https://cdn.jsdelivr.net/npm/alpinejs@3.10.2/dist/cdn.min.js';
    alpineScript.defer = true;
    document.head.appendChild(alpineScript);

    // Wait for Alpine.js to load, then initialize the widget
    alpineScript.onload = initializeWidget;
})();
