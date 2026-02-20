<div data-x-data="{
    loading: false,
    success: false,
    error: '',

    // Биндинг для формы (отправка)
    formBind: {
        ['data-x-on:submit.prevent']() { this.submitContact(this.$el) }
    },

    // Биндинг для кнопки 'Написать еще'
    retryBtn: {
        ['data-x-on:click']() { this.success = false }
    },

    // Биндинг для кнопки отправки (disabled)
    submitBtn: {
        ['data-x-bind:disabled']() { return this.loading }
    },

    // Отправка формы
    async submitContact(form) {
        this.loading = true;
        this.error = '';
        this.success = false;

        const formData = new FormData(form);
        formData.append('action', 'send_contact_form');

        try {
            const response = await fetch('{{ admin_url('admin-ajax.php') }}', {
                method: 'POST',
                body: formData
            });

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Ошибка сервера');
            }

            const result = await response.json();

            if (result.success) {
                this.success = true;
                form.reset();
            } else {
                this.error = result.data.message || 'Произошла ошибка.';
            }
        } catch (err) {
            console.error(err);
            this.error = 'Ошибка соединения. Попробуйте позже.';
        } finally {
            this.loading = false;
        }
    }
}" class="w-full relative">

    {{-- УСПЕХ --}}
    <div data-x-show="success" style="display: none;" data-x-transition
        class="bg-black border border-green-600 text-green-400 p-8 text-center">
        <div class="w-16 h-16 bg-green-900/50 text-green-500 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                </path>
            </svg>
        </div>
        <h3 class="text-2xl font-bold mb-2">Сообщение отправлено!</h3>
        <p class="text-gray-300 mb-6">Наш менеджер свяжется с вами в ближайшее время.</p>

        {{-- Кнопка с биндингом --}}
        <button type="button" data-x-bind="retryBtn"
            class="text-sm font-bold text-green-400 hover:text-green-300 uppercase tracking-wide border-b border-green-800 hover:border-green-600 transition-colors pb-1">
            Написать еще
        </button>
    </div>

    {{-- ФОРМА --}}
    <form data-x-bind="formBind" data-x-show="!success" data-x-transition class="space-y-6">

        <input type="hidden" name="nonce" value="{{ wp_create_nonce('contact_form_action') }}">

        <div>
            <label class="block text-xs font-bold uppercase text-gray-400 mb-2 tracking-wider">Ваше имя</label>
            <input type="text" name="name" required placeholder="Иван"
                class="w-full bg-gray-900 border border-gray-700 px-4 py-3 text-black placeholder-gray-500 focus:outline-none focus:border-red-500 focus:bg-gray-800 transition-all shadow-sm">
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-400 mb-2 tracking-wider">Номер телефона</label>
            <input type="tel" name="phone" required placeholder="+7 999 123-45-67"
                class="w-full bg-gray-900 border border-gray-700 px-4 py-3 text-black placeholder-gray-500 focus:outline-none focus:border-red-500 focus:bg-gray-800 transition-all shadow-sm">
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-400 mb-2 tracking-wider">Email</label>
            <input type="email" name="email" placeholder="mail@example.com"
                class="w-full bg-gray-900 border border-gray-700 px-4 py-3 text-black placeholder-gray-500 focus:outline-none focus:border-red-500 focus:bg-gray-800 transition-all shadow-sm">
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-400 mb-2 tracking-wider">Комментарий</label>
            <textarea name="message" rows="4" placeholder="Ваш вопрос или комментарий..."
                class="w-full bg-gray-900 border border-gray-700 px-4 py-3 text-black placeholder-gray-500 focus:outline-none focus:border-red-500 focus:bg-gray-800 transition-all shadow-sm resize-none"></textarea>
        </div>

        {{-- ОШИБКА --}}
        <div data-x-show="error" style="display: none;"
            class="text-red-400 text-sm font-bold bg-black border border-red-800 p-4 flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span data-x-text="error"></span>
        </div>

        {{-- КНОПКА ОТПРАВКИ --}}
        <button type="submit" data-x-bind="submitBtn"
            class="w-full bg-red-600 text-black font-bold uppercase py-4  shadow-lg hover:bg-red-700 hover:shadow-xl transition transform active:scale-[0.99] flex justify-center items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed tracking-wide">
            <span data-x-show="!loading">Отправить сообщение</span>

            <span data-x-show="loading" style="display: none;" class="flex items-center gap-2">
                <svg class="animate-spin h-5 w-5 text-black" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                Отправка...
            </span>
        </button>

        <p class="text-center text-xs text-gray-400 mt-4">
            Ваши данные конфиденциальны и не будут переданы третьим лицам.
        </p>
    </form>
</div>
