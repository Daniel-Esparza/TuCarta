import Alpine from 'alpinejs';

Alpine.data('disponibilidad', (url, disponible, token) => ({
    disponible,
    enviando: false,
    fallo: false,

    async alternar() {
        this.enviando = true;
        this.fallo = false;

        try {
            const respuesta = await fetch(url, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
            });

            if (!respuesta.ok) {
                throw new Error(respuesta.status);
            }

            this.disponible = (await respuesta.json()).disponible;
        } catch {
            this.fallo = true;
        } finally {
            this.enviando = false;
        }
    },
}));

window.Alpine = Alpine;
Alpine.start();
