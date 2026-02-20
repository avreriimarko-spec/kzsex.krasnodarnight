{{--
  Template Name: Добавить анкету
--}}
@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-12 max-w-4xl">
        {{-- Основной контейнер: Черный фон, красная рамка --}}
        <article class="bg-black p-6 md:p-12 border border-[#cd1d46]">
            
            {{-- Хедер страницы --}}
            <header class="mb-8 border-b border-[#cd1d46]/30 pb-8">
                <h1 class="text-3xl md:text-5xl font-bold text-[#cd1d46] uppercase tracking-tight mb-4">
                    {!! get_field('custom_h1') ?: get_the_title() !!}
                </h1>
                <div class="text-gray-300">
                    Заполните форму ниже для добавления вашей анкеты на сайт
                </div>
            </header>

            {{-- Форма добавления анкеты --}}
            <form id="addProfileForm" class="space-y-6">
                @csrf
                <input type="hidden" name="action" value="submit_profile_application">
                <input type="hidden" name="_wpnonce" value="{{ wp_create_nonce('profile_form_nonce') }}">
                
                {{-- Имя --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-300 mb-2">
                        Ваше имя <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        required
                        class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-black placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#cd1d46] focus:border-transparent"
                        placeholder="Введите ваше имя"
                    >
                </div>

                {{-- Номер телефона --}}
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-300 mb-2">
                        Номер телефона <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        required
                        class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-black placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#cd1d46] focus:border-transparent"
                        placeholder="+7 (XXX) XXX-XX-XX"
                    >
                </div>

                {{-- Способ связи --}}
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        Предпочтительный способ связи <span class="text-red-500">*</span>
                    </label>
                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="radio" name="contact_method" value="telegram" class="mr-3 text-[#cd1d46] focus:ring-[#cd1d46]">
                            <span class="text-gray-300">Telegram</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="contact_method" value="phone" class="mr-3 text-[#cd1d46] focus:ring-[#cd1d46]">
                            <span class="text-gray-300">Телефон</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="contact_method" value="whatsapp" class="mr-3 text-[#cd1d46] focus:ring-[#cd1d46]">
                            <span class="text-gray-300">WhatsApp</span>
                        </label>
                    </div>
                </div>

                {{-- Имя пользователя (для Telegram) --}}
                <div id="usernameField" class="hidden">
                    <label for="username" class="block text-sm font-medium text-gray-300 mb-2">
                        Имя пользователя в Telegram <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-black placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#cd1d46] focus:border-transparent"
                        placeholder="@username"
                    >
                </div>

                {{-- Время работы --}}
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        Время работы <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="work_time_from" class="block text-xs text-gray-400 mb-1">С</label>
                            <input 
                                type="time" 
                                id="work_time_from" 
                                name="work_time_from" 
                                class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-black focus:outline-none focus:ring-2 focus:ring-[#cd1d46] focus:border-transparent"
                            >
                        </div>
                        <div>
                            <label for="work_time_to" class="block text-xs text-gray-400 mb-1">До</label>
                            <input 
                                type="time" 
                                id="work_time_to" 
                                name="work_time_to" 
                                class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-black focus:outline-none focus:ring-2 focus:ring-[#cd1d46] focus:border-transparent"
                            >
                        </div>
                    </div>
                    
                    {{-- Круглосуточно --}}
                    <div class="mt-3">
                        <label class="flex items-center">
                            <input type="checkbox" id="is_24_7" name="is_24_7" class="mr-3 text-[#cd1d46] focus:ring-[#cd1d46] rounded">
                            <span class="text-gray-300">Работаю круглосуточно</span>
                        </label>
                    </div>
                </div>

                {{-- Дополнительная информация --}}
                <div>
                    <label for="additional_info" class="block text-sm font-medium text-gray-300 mb-2">
                        Дополнительная информация
                    </label>
                    <textarea 
                        id="additional_info" 
                        name="additional_info" 
                        rows="4"
                        class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-black placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#cd1d46] focus:border-transparent"
                        placeholder="Расскажите немного о себе, ваших услугах и предпочтениях..."
                    ></textarea>
                </div>

                {{-- Кнопка отправки --}}
                <div class="pt-4">
                    <button 
                        type="submit" 
                        id="submitBtn"
                        class="w-full md:w-auto px-8 py-4 bg-[#cd1d46] text-black font-bold rounded-lg hover:bg-[#b01a38] transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-[#cd1d46] focus:ring-offset-2 focus:ring-offset-black disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span id="submitText">Отправить заявку</span>
                        <span id="submitLoader" class="hidden">
                            <svg class="animate-spin h-5 w-5 inline-block mr-2" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Отправка...
                        </span>
                    </button>
                </div>

                {{-- Сообщение об успехе/ошибке --}}
                <div id="formMessage" class="hidden p-4 rounded-lg"></div>
            </form>
        </article>
    </div>

    {{-- JavaScript для формы --}}
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('addProfileForm');
        const contactMethodRadios = document.querySelectorAll('input[name="contact_method"]');
        const usernameField = document.getElementById('usernameField');
        const workTimeFrom = document.getElementById('work_time_from');
        const workTimeTo = document.getElementById('work_time_to');
        const is247Checkbox = document.getElementById('is_24_7');
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const submitLoader = document.getElementById('submitLoader');
        const formMessage = document.getElementById('formMessage');

        // Показать/скрыть поле имени пользователя в зависимости от способа связи
        contactMethodRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'telegram') {
                    usernameField.classList.remove('hidden');
                    document.getElementById('username').setAttribute('required', 'required');
                } else {
                    usernameField.classList.add('hidden');
                    document.getElementById('username').removeAttribute('required');
                }
            });
        });

        // Обработка тумблера "круглосуточно"
        is247Checkbox.addEventListener('change', function() {
            if (this.checked) {
                workTimeFrom.disabled = true;
                workTimeTo.disabled = true;
                workTimeFrom.value = '';
                workTimeTo.value = '';
            } else {
                workTimeFrom.disabled = false;
                workTimeTo.disabled = false;
            }
        });

        // Обработка отправки формы
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Показываем загрузку
            submitBtn.disabled = true;
            submitText.classList.add('hidden');
            submitLoader.classList.remove('hidden');
            hideMessage();

            const formData = new FormData(form);
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', 'Ваша заявка успешно отправлена! Мы свяжемся с вами в ближайшее время.');
                    form.reset();
                    usernameField.classList.add('hidden');
                } else {
                    showMessage('error', data.data || 'Произошла ошибка. Пожалуйста, попробуйте еще раз.');
                }
            })
            .catch(error => {
                showMessage('error', 'Произошла ошибка. Пожалуйста, попробуйте еще раз.');
            })
            .finally(() => {
                // Возвращаем кнопку в исходное состояние
                submitBtn.disabled = false;
                submitText.classList.remove('hidden');
                submitLoader.classList.add('hidden');
            });
        });

        function showMessage(type, message) {
            formMessage.classList.remove('hidden');
            formMessage.className = formMessage.className.replace(/bg-\w+-100|text-\w+-800|bg-green-100|text-green-800|bg-red-100|text-red-800/g, '');
            
            if (type === 'success') {
                formMessage.classList.add('bg-black', 'border', 'border-green-600', 'text-green-400', 'p-4', 'rounded-lg');
            } else {
                formMessage.classList.add('bg-black', 'border', 'border-red-600', 'text-red-400', 'p-4', 'rounded-lg');
            }
            
            formMessage.textContent = message;
        }

        function hideMessage() {
            formMessage.classList.add('hidden');
        }
    });
    </script>
@endsection
