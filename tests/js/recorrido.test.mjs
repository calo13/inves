import { test } from 'node:test';
import assert from 'node:assert/strict';
import { colocarTarjeta, crearRecorrido } from '../../resources/js/io-recorrido.js';

const pasos = [
    { url: 'http://localhost/', objetivos: [] },
    { url: 'http://localhost/tickets', objetivos: ['control-condicional', 'registro'] },
    { url: 'http://localhost/', objetivos: [] },
];

function navegador(datos = new Map(), ruta = '/') {
    const shell = { inert: false };
    const boton = { focus() {}, isConnected: true };
    const documento = {
        body: { style: { overflow: '' } }, activeElement: boton,
        getElementById: () => shell,
        querySelector: selector => {
            if (selector === '[data-tour-abrir]') return boton;
            if (selector !== '[data-tour="registro"]') return null;
            return {
                scrollIntoView() {}, closest: () => null,
                getBoundingClientRect: () => ({ left: 20, top: 20, right: 220, bottom: 60, width: 200, height: 40 }),
            };
        },
    };
    return {
        document: documento, innerWidth: 1200, innerHeight: 800,
        location: { origin: 'http://localhost', pathname: ruta, assign(url) { this.destino = url; } },
        sessionStorage: { getItem: key => datos.get(key) ?? null, setItem: (key, value) => datos.set(key, value), removeItem: key => datos.delete(key) },
        addEventListener() {}, removeEventListener() {},
    };
}

function recorrido(entorno, iniciar = false, usuario = 1) {
    const tour = crearRecorrido({ pasos, iniciar, usuario }, entorno);
    tour.$nextTick = fn => fn();
    tour.$refs = { dialogo: { focus() {}, querySelectorAll: () => [], offsetWidth: 432, offsetHeight: 350 }, cuerpo: { clientHeight: 200, scrollHeight: 200 } };
    tour.init();
    return tour;
}

test('avanza entre módulos y retoma el mismo paso al cargar la página', () => {
    const datos = new Map();
    const primera = navegador(datos);
    const tour = recorrido(primera, true);
    assert.equal(tour.abierto, true);
    tour.mover(1);
    assert.equal(primera.location.destino, 'http://localhost/tickets');
    const segunda = navegador(datos, '/tickets');
    const retomado = recorrido(segunda);
    assert.equal(retomado.indice, 1);
    assert.equal(retomado.abierto, true);
    assert.equal(segunda.document.getElementById().inert, true);
    assert.deepEqual(retomado.foco, { left: 13, top: 13, width: 214, height: 54 });
    retomado.mover(-1);
    assert.equal(segunda.location.destino, 'http://localhost/');
});

test('omitir restaura la pantalla y no vuelve a abrir hasta otro inicio o repetición manual', () => {
    const datos = new Map();
    const ventana = navegador(datos);
    const tour = recorrido(ventana, true);
    tour.cerrar();
    assert.equal(datos.size, 0);
    assert.equal(ventana.document.body.style.overflow, '');
    assert.equal(ventana.document.getElementById().inert, false);
    assert.equal(recorrido(navegador(datos)).abierto, false);
    tour.comenzar();
    assert.equal(tour.abierto, true);
    assert.equal(tour.indice, 0);
});

test('un nuevo inicio comienza desde el principio y cada usuario tiene su propio recorrido', () => {
    const datos = new Map([['io-recorrido-v2:1', '{"indice":1}']]);
    assert.equal(recorrido(navegador(datos), false, 2).abierto, false);
    assert.equal(recorrido(navegador(datos), true, 1).indice, 0);
});

test('Escape permite omitir y el almacenamiento bloqueado no interrumpe la guía', () => {
    const ventana = navegador();
    ventana.sessionStorage.setItem = () => { throw new Error('Bloqueado'); };
    const tour = recorrido(ventana, true);
    tour.mover(1);
    assert.equal(tour.abierto, true);
    assert.equal(tour.indice, 1);
    assert.equal(ventana.location.destino, undefined);
    tour.tecla({ key: 'Escape', preventDefault() {} });
    assert.equal(tour.abierto, false);
});

test('ignora una posición guardada fuera de los pasos disponibles', () => {
    assert.equal(recorrido(navegador(new Map([['io-recorrido-v2:1', '{"indice":999}']]))).abierto, false);
});

test('ubica la explicación al lado del control sin taparlo en escritorio', () => {
    const foco = { left: 820, top: 350, width: 150, height: 50 };
    const tarjeta = { width: 432, height: 340 };
    const posicion = colocarTarjeta(foco, tarjeta, { width: 1024, height: 700 });
    assert.equal(posicion.lado, 'izquierda');
    assert.ok(posicion.left >= 16);
    assert.ok(posicion.left + tarjeta.width < foco.left);
    assert.ok(posicion.top + tarjeta.height <= 684);
});

test('en móvil limita el alto de la tarjeta al espacio disponible sin cubrir el control', () => {
    const foco = { left: 20, top: 205, width: 350, height: 82 };
    const tarjeta = { width: 358, height: 470 };
    const posicion = colocarTarjeta(foco, tarjeta, { width: 390, height: 660 });
    assert.equal(posicion.lado, 'abajo');
    assert.ok(posicion.top > foco.top + foco.height);
    assert.ok(posicion.maxHeight < tarjeta.height);
    assert.equal(posicion.top + posicion.maxHeight, 644);
    assert.equal(posicion.left, 16);
});
