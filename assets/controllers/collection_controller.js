import { Controller } from '@hotwired/stimulus';

/*
 * Dynamic Symfony CollectionType rows.
 *
 * Markup:
 *   <div data-controller="collection"
 *        data-collection-prototype-value="…"
 *        data-collection-index-value="N">
 *       <div data-collection-target="container">…existing rows…</div>
 *       <button type="button" data-action="collection#add">+ row</button>
 *   </div>
 *
 * Each row should have a button with data-action="collection#remove" inside.
 */
export default class extends Controller {
    static targets = ['container'];
    static values = {
        prototype: String,
        index: Number,
    };

    add(event) {
        event.preventDefault();
        const html = this.prototypeValue.replaceAll('__name__', String(this.indexValue));
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        const node = wrapper.firstElementChild;
        if (node !== null) {
            this.containerTarget.appendChild(node);
        }
        this.indexValue += 1;
    }

    remove(event) {
        event.preventDefault();
        const row = event.target.closest('[data-collection-row]');
        if (row !== null) {
            row.remove();
        }
    }
}
