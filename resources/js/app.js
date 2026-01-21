import './bootstrap';
import { registerSW } from 'virtual:pwa-register';

if ('serviceWorker' in navigator) {
    registerSW({
        immediate: true,
        onNeedRefresh() {
            console.log('Nova versão disponível! Atualizando...');
        },
        onOfflineReady() {
            console.log('App pronto para uso offline');
        },
    });
}
