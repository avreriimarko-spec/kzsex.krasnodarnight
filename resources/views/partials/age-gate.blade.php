{{--
    Age Gate (18+)
    Валидный синтаксис: события вынесены в data-x-bind, чтобы убрать двоеточия из HTML.
--}}
<div data-x-data="{
    verified: localStorage.getItem('age_verified') === 'true',

    confirm() {
        localStorage.setItem('age_verified', 'true');
        this.verified = true;
    },

    exit() {
        window.location.href = 'https://google.com';
    },

    // Биндинг для кнопки 'Мне есть 18'
    confirmBtn: {
        ['data-x-on:click']() { this.confirm() }
    },

    // Биндинг для кнопки 'Покинуть сайт'
    exitBtn: {
        ['data-x-on:click']() { this.exit() }
    }
}" data-x-show="!verified" data-x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900 bg-opacity-95 backdrop-blur-md">
    <div class="bg-white shadow-2xl p-8 max-w-md w-full text-center border-t-4 border-red-600 mx-4">

        <div class="mb-6">
            <div class="w-20 h-20 bg-red-100  flex items-center justify-center mx-auto mb-4">
                <span class="text-3xl font-bold text-red-600">18+</span>
            </div>

            <h2 class="text-2xl font-bold text-gray-900 capitalize mb-2">Ограничение доступа</h2>
            <p class="text-gray-600 text-sm leading-relaxed">
                Этот сайт содержит материалы для взрослых. Вы должны быть старше 18 лет, чтобы просматривать его
                содержимое.
            </p>
        </div>

        <div class="flex flex-col gap-3">
            {{-- Используем data-x-bind вместо data-x-on:click --}}
            <button data-x-bind="confirmBtn"
                class="w-full bg-red-600 hover:bg-red-700 text-black font-bold capitalize py-4  shadow-lg transition-transform transform hover:scale-[1.02]">
                Мне есть 18 лет
            </button>

            {{-- Используем data-x-bind вместо data-x-on:click --}}
            <button data-x-bind="exitBtn"
                class="w-full bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold capitalize py-3  transition-colors">
                Покинуть сайт
            </button>
        </div>

        <p class="mt-6 text-xs text-gray-400">
            Нажимая кнопку входа, вы подтверждаете свое совершеннолетие и согласие с правилами использования сайта.
        </p>
    </div>
</div>
