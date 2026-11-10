const MARGEN = 16;
const SEPARACION = 14;
const limitar = (valor, minimo, maximo) => Math.max(minimo, Math.min(valor, maximo));

// La tarjeta usa el espacio libre junto al control. En móvil puede desplazarse su texto.
export function colocarTarjeta(foco, tarjeta, vista) {
    const derecha = foco.left + foco.width;
    const abajo = foco.top + foco.height;
    const centroX = foco.left + foco.width / 2;
    const centroY = foco.top + foco.height / 2;
    const alturaMaxima = vista.height - MARGEN * 2;
    const altura = Math.min(tarjeta.height, alturaMaxima);
    const cabeDerecha = vista.width - MARGEN - derecha >= tarjeta.width + SEPARACION;
    const cabeIzquierda = foco.left - MARGEN >= tarjeta.width + SEPARACION;

    if (cabeDerecha || cabeIzquierda) {
        const top = limitar(centroY - altura / 2, MARGEN, vista.height - MARGEN - altura);
        return {
            left: cabeDerecha ? derecha + SEPARACION : foco.left - tarjeta.width - SEPARACION,
            top,
            maxHeight: alturaMaxima,
            lado: cabeDerecha ? 'derecha' : 'izquierda',
            ancla: limitar(centroY - top, 16, altura - 16),
        };
    }

    const espacioAbajo = vista.height - MARGEN - abajo - SEPARACION;
    const espacioArriba = foco.top - MARGEN - SEPARACION;
    const colocarAbajo = espacioAbajo >= altura || (espacioArriba < altura && espacioAbajo >= espacioArriba);
    const maxHeight = colocarAbajo ? espacioAbajo : espacioArriba;
    const left = limitar(centroX - tarjeta.width / 2, MARGEN, vista.width - MARGEN - tarjeta.width);

    return {
        left,
        top: colocarAbajo ? abajo + SEPARACION : foco.top - SEPARACION - Math.min(altura, maxHeight),
        maxHeight,
        lado: colocarAbajo ? 'abajo' : 'arriba',
        ancla: limitar(centroX - left, 16, tarjeta.width - 16),
    };
}

// Livewire proporciona Alpine. El recorrido no inicia otra instancia ni modifica datos.
export function crearRecorrido({ pasos, iniciar, usuario }, entorno = window) {
    const clave = `io-recorrido-v2:${usuario}`;
    let observador;
    let cuadro;

    return {
        pasos,
        abierto: false,
        indice: 0,
        foco: null,
        posicion: null,
        persistencia: true,
        anteriorFoco: null,
        overflowAnterior: '',
        shellInerte: false,
        alReubicar: null,

        get paso() { return this.pasos[this.indice]; },
        get ultimo() { return this.indice === this.pasos.length - 1; },

        init() {
            this.alReubicar = () => {
                if (!this.abierto || cuadro != null) return;
                cuadro = entorno.requestAnimationFrame(() => {
                    cuadro = null;
                    this.ubicar();
                });
            };
            entorno.addEventListener('resize', this.alReubicar);
            entorno.addEventListener('scroll', this.alReubicar, { passive: true });
            if (iniciar) {
                this.comenzar();
                return;
            }
            try {
                const guardado = JSON.parse(entorno.sessionStorage.getItem(clave));
                if (guardado && Number.isInteger(guardado.indice) && guardado.indice >= 0 && guardado.indice < this.pasos.length) {
                    this.indice = guardado.indice;
                    this.mostrar();
                }
            } catch { this.persistencia = false; }
        },

        guardar() {
            try { entorno.sessionStorage.setItem(clave, JSON.stringify({ indice: this.indice })); }
            catch { this.persistencia = false; }
        },

        comenzar() {
            this.indice = 0;
            this.guardar();
            this.mostrar();
        },

        mostrar() {
            if (!this.abierto) {
                this.anteriorFoco = entorno.document.activeElement;
                this.overflowAnterior = entorno.document.body.style.overflow;
                const shell = entorno.document.getElementById('io-app-shell');
                this.shellInerte = shell?.inert ?? false;
                if (shell) shell.inert = true;
            }
            this.abierto = true;
            entorno.document.body.style.overflow = 'hidden';
            this.$nextTick(() => {
                if (!this.abierto) return;
                const objetivo = this.encontrarObjetivo();
                this.ubicar(true, objetivo);
                observador?.disconnect();
                if (objetivo && entorno.ResizeObserver) {
                    observador ??= new entorno.ResizeObserver(this.alReubicar);
                    observador.observe(objetivo);
                    observador.observe(this.$refs.dialogo);
                    const shell = entorno.document.getElementById('io-app-shell');
                    if (shell) observador.observe(shell);
                }
                this.$refs.cuerpo.scrollTop = 0;
                this.$refs.dialogo.focus({ preventScroll: true });
            });
        },

        encontrarObjetivo() {
            const destino = new URL(this.paso.url, entorno.location.origin);
            if (destino.origin !== entorno.location.origin || destino.pathname !== entorno.location.pathname) return null;
            for (const nombre of this.paso.objetivos) {
                const objetivo = entorno.document.querySelector(`[data-tour="${nombre}"]`);
                const caja = objetivo?.getBoundingClientRect();
                if (caja?.width && caja.height) return objetivo;
            }
            return null;
        },

        ubicar(desplazar = false, objetivo = this.encontrarObjetivo()) {
            if (!this.abierto) return;
            if (!objetivo) {
                this.foco = this.posicion = null;
                return;
            }
            const vista = { width: entorno.innerWidth, height: entorno.innerHeight };
            if (desplazar) {
                objetivo.scrollIntoView({ block: vista.width < 760 ? 'start' : 'center', inline: 'nearest', behavior: 'instant' });
                if (objetivo.getBoundingClientRect().top < MARGEN) entorno.scrollBy(0, -MARGEN);
            }

            const r = objetivo.getBoundingClientRect();
            const limiteTabla = objetivo.closest('.overflow-x-auto')?.getBoundingClientRect();
            const left = Math.max(4, r.left - 7, (limiteTabla?.left ?? 0) - 7);
            const right = Math.min(vista.width - 4, r.right + 7, (limiteTabla?.right ?? vista.width) + 7);
            const top = Math.max(4, r.top - 7);
            const bottom = Math.min(vista.height - 4, r.bottom + 7);
            if (right <= left || bottom <= top) {
                this.foco = this.posicion = null;
                return;
            }

            const { dialogo, cuerpo } = this.$refs;
            const tarjeta = { width: dialogo.offsetWidth, height: dialogo.offsetHeight - cuerpo.clientHeight + cuerpo.scrollHeight };
            const foco = { left, top, width: right - left, height: bottom - top };
            let posicion = colocarTarjeta(foco, tarjeta, vista);
            if (posicion.maxHeight < Math.min(160, vista.height / 2)) {
                // Una región alta (como la gráfica) puede ocupar casi todo el alto.
                // Conserva visible su parte superior y deja espacio para leer la explicación.
                foco.height = Math.min(foco.height, vista.height / 2 - SEPARACION);
                posicion = colocarTarjeta(foco, tarjeta, vista);
            }
            this.foco = foco;
            this.posicion = posicion;
        },

        mover(diferencia) {
            const nuevo = this.indice + diferencia;
            if (nuevo < 0 || nuevo >= this.pasos.length) return;
            this.indice = nuevo;
            this.foco = this.posicion = null;
            this.guardar();
            const destino = new URL(this.paso.url, entorno.location.origin);
            if (this.persistencia && destino.origin === entorno.location.origin && destino.pathname !== entorno.location.pathname) {
                entorno.location.assign(destino.href);
                return;
            }
            this.mostrar();
        },

        cerrar() {
            this.abierto = false;
            this.foco = this.posicion = null;
            observador?.disconnect();
            try { entorno.sessionStorage.removeItem(clave); } catch { /* Almacenamiento restringido. */ }
            const shell = entorno.document.getElementById('io-app-shell');
            if (shell) shell.inert = this.shellInerte;
            entorno.document.body.style.overflow = this.overflowAnterior;
            if (this.anteriorFoco?.isConnected && this.anteriorFoco !== entorno.document.body) this.anteriorFoco.focus({ preventScroll: true });
            else entorno.document.querySelector('[data-tour-abrir]')?.focus({ preventScroll: true });
        },

        tecla(evento) {
            if (!this.abierto) return;
            if (evento.key === 'Escape') { evento.preventDefault(); this.cerrar(); }
            if (evento.key !== 'Tab') return;
            const elementos = [...this.$refs.dialogo.querySelectorAll('button:not([disabled]), a[href]')];
            const primero = elementos[0], ultimo = elementos[elementos.length - 1];
            const activo = entorno.document.activeElement;
            if (evento.shiftKey && (activo === primero || activo === this.$refs.dialogo)) {
                evento.preventDefault(); ultimo?.focus();
            } else if (!evento.shiftKey && (activo === ultimo || activo === this.$refs.dialogo)) {
                evento.preventDefault(); primero?.focus();
            }
        },

        destroy() {
            entorno.removeEventListener('resize', this.alReubicar);
            entorno.removeEventListener('scroll', this.alReubicar);
            observador?.disconnect();
            if (cuadro != null) entorno.cancelAnimationFrame(cuadro);
            if (this.abierto) {
                const shell = entorno.document.getElementById('io-app-shell');
                if (shell) shell.inert = this.shellInerte;
                entorno.document.body.style.overflow = this.overflowAnterior;
            }
        },
    };
}
