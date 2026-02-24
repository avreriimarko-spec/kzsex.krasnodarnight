<div data-x-data="{
    loading: false,
    success: false,
    error: '',
    files: [],

    selectedCountry: '{{ $phoneSettings['default'] }}',
    masks: {{ json_encode($phoneSettings['masks']) }},
    countries: {{ json_encode($phoneSettings['list']) }},

    handleFiles(e) {
        this.files = Array.from(e.target.files);
    },

    formatPhone(e) {
        e.target.value = e.target.value.replace(/[^0-9+\-() ]/g, '');
    },

    async submitForm(e) {
        this.loading = true;
        this.error = '';
        this.success = false;

        const formData = new FormData(e.target);
        formData.append('action', 'send_work_form');

        try {
            const response = await fetch('{{ admin_url('admin-ajax.php') }}', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.success = true;
                e.target.reset();
                this.files = [];
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
}" class="max-w-2xl mx-auto relative">

    {{-- СООБЩЕНИЕ ОБ УСПЕХЕ --}}
    <div data-x-show="success" style="display: none;" data-x-transition
        class="bg-black border border-green-600 text-green-400 p-6 text-center mb-6">
        <div class="w-16 h-16 bg-green-900/50 text-green-500 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h3 class="text-xl font-bold mb-2">Анкета отправлена!</h3>
        <p class="text-gray-300">Мы свяжемся с вами в ближайшее время.</p>
        <button type="button" data-x-on:click="success = false"
            class="mt-4 text-sm font-bold text-green-400 hover:text-green-300">
            Отправить еще одну
        </button>
    </div>

    <form data-x-on:submit.prevent="submitForm" data-x-show="!success" data-x-transition class="space-y-6"
        enctype="multipart/form-data">

        <input type="hidden" name="nonce" value="{{ wp_create_nonce('work_form_action') }}">

        {{-- ИМЯ И ВОЗРАСТ --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-bold capitalize text-gray-700 mb-2">Ваше имя *</label>
                <input type="text" name="name" required placeholder="Анна"
                    class="w-full bg-gray-50 border border-gray-200  px-4 py-3 focus:outline-none focus:border-red-500 focus:bg-white transition-colors">
            </div>

            <div>
                <label class="block text-xs font-bold capitalize text-gray-700 mb-2">Возраст</label>
                <input type="number" name="age" placeholder="21"
                    class="w-full bg-gray-50 border border-gray-200  px-4 py-3 focus:outline-none focus:border-red-500 focus:bg-white transition-colors">
            </div>
        </div>

        {{-- ТЕЛЕФОН С ВЫБОРОМ СТРАНЫ --}}
        <div>
            <label class="block text-xs font-bold capitalize text-gray-700 mb-2">Телефон / Мессенджер *</label>
            <div
                class="flex items-stretch bg-gray-50 border border-gray-200  overflow-hidden focus-within:border-red-500 focus-within:bg-white transition-all shadow-sm">
                <select data-x-model="selectedCountry"
                    class="w-24 sm:w-28 flex-shrink-0 bg-gray-100 border-none border-r border-gray-200 px-2 py-3 focus:outline-none focus:ring-0 text-sm cursor-pointer hover:bg-gray-200 transition-colors">
                    <template data-x-for="item in countries" :key="item.code">
                        <option :value="item.code" data-x-text="item.label"></option>
                    </template>
                </select>

                <input type="tel" name="phone" required
                    data-x-bind:placeholder="masks[selectedCountry] || '+7 (999) 999-99-99'"
                    data-x-mask:dynamic="masks[selectedCountry]" data-x-on:input="formatPhone"
                    class="flex-1 min-w-0 bg-transparent border-none px-4 py-3 focus:outline-none focus:ring-0 transition-colors">
            </div>
        </div>

        {{-- ВОТ ОНО: ПОЛЕ О СЕБЕ --}}
        <div>
            <label class="block text-xs font-bold capitalize text-gray-700 mb-2">О себе (параметры, опыт)</label>
            <textarea name="about" rows="4" placeholder="Рост 170, вес 55..."
                class="w-full bg-gray-50 border border-gray-200  px-4 py-3 focus:outline-none focus:border-red-500 focus:bg-white transition-colors"></textarea>
        </div>

        {{-- ЗАГРУЗКА ФОТО --}}
        <div>
            <label class="block text-xs font-bold capitalize text-gray-700 mb-2">Фотографии (макс 5 шт)</label>
            <div
                class="relative border-2 border-dashed border-gray-300  p-6 text-center hover:bg-gray-50 transition-colors cursor-pointer group">
                <input type="file" name="photos[]" multiple accept="image/*" data-x-on:change="handleFiles"
                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">

                <div class="flex flex-col items-center justify-center text-gray-500">
                    <svg class="w-8 h-8 mb-2 group-hover:text-red-500 transition-colors" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                    <span class="text-sm font-medium">Нажмите, чтобы выбрать фото</span>
                    <span class="text-xs text-gray-400 mt-1" data-x-show="files.length === 0">JPG, PNG до 5MB</span>
                </div>
            </div>

            <div data-x-show="files.length > 0" style="display: none;" class="mt-3 space-y-1">
                <template data-x-for="file in files" :key="file.name">
                    <div class="text-xs text-gray-600 flex items-center">
                        <svg class="w-3 h-3 text-green-500 mr-1" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        <span data-x-text="file.name"></span>
                    </div>
                </template>
            </div>
        </div>

        {{-- ОШИБКА --}}
        <div data-x-show="error" style="display: none;"
            class="text-red-400 text-sm font-bold bg-black border border-red-800 p-3 rounded" data-x-text="error">
        </div>

        {{-- КНОПКА --}}
        <button type="submit" data-x-bind:disabled="loading"
            class="w-full bg-red-600 text-black font-bold capitalize py-4  shadow-lg hover:bg-red-700 hover:shadow-xl transition transform active:scale-[0.99] flex justify-center items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
            <span data-x-show="!loading">Отправить анкету</span>
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

        <p class="text-center text-xs text-gray-400">
            Нажимая кнопку, вы соглашаетесь с <a href="/privacy-policy/"
                class="underline hover:text-gray-600">политикой конфиденциальности</a>.
        </p>
    </form>
</div>
