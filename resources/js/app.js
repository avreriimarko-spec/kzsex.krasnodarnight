import.meta.glob([
  '../images/**',
  '../fonts/**',
]);

import { Fancybox } from "@fancyapps/ui";
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import mask from '@alpinejs/mask';

const main = async (err) => {
  if (err) {
    console.error(err);
  }

  // Подключаем плагины
  Alpine.plugin(collapse);
  Alpine.plugin(mask);

  // Настраиваем префикс для Alpine
  Alpine.prefix('data-x-');

  // ВАЖНО: Настраиваем разделители для bind и on
  // Вместо : используем -
  Alpine.magic('bind', () => { });
  Alpine.magic('on', () => { });

  window.Alpine = Alpine;
  Alpine.start();

  Fancybox.bind("[data-fancybox]", {});
};

const domReady = (callback) => {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', callback);
  } else {
    callback();
  }
};

domReady(main);

if (import.meta.hot) {
  import.meta.hot.accept(main);
}
