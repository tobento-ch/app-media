const imageEditors = (function(window, document) {
    'use strict';
    
    class Eventer {
        constructor() {
            this.listeners = {};
        }
        listen(eventName, listener) {
            if (typeof this.listeners[eventName] === 'undefined') {
                this.listeners[eventName] = [];
            }

            this.listeners[eventName].push(listener);
        }
        fire(eventName, parameters) {
            if (typeof this.listeners[eventName] === 'object') {
                this.listeners[eventName].forEach(listener => {
                    if (typeof listener === 'function') {
                        if (parameters instanceof Array) {
                            listener(...parameters);
                        } else if (parameters instanceof Object) {
                            listener(parameters);
                        }
                    }
                });
            }

            return parameters;
        }
    }
    
    class ImageEditor {
        constructor(formEl, config) {
            this.formEl = formEl;
            this.config = config;
            this.id = config.id;
            this.imgEl = this.formEl.querySelector('[data-image]');
            this.imgSrc = this.imgEl.getAttribute('src');
            this.events = new Eventer();
            this.actionOpen = null;
            
            if (typeof this.config['actionOpen'] !== 'undefined') {
                this.actionOpen = this.config['actionOpen'];
            }
            
            // actions:
            this.actions = {};
            this.formEl.querySelectorAll('[data-image-action]').forEach(el => {
                const action = new ImageAction(this, el);
                
                if (action.name === this.actionOpen) {
                    setTimeout(() => {
                        action.open();
                    }, 300);
                }
                
                this.actions[action.name] = action;
            });
            
            // events:
            ['keyup', 'change'].forEach(evt => {
                this.formEl.addEventListener(evt, (e) => {
                    if (e.target.getAttribute('data-image-update') === '0') {
                        return;
                    }
                    
                    if (e.type === 'keyup') {
                        setTimeout(() => {
                            this.updateImage();
                        }, 200);
                    } else {
                        this.updateImage();
                    }
                });
            });
        }
        registerAction(el, name, trigger) {
            el.setAttribute('data-image-action', name);
            el.setAttribute('data-image-action-trigger', trigger);
            
            const action = new ImageAction(this, el);
            this.actions[action.name] = action;
        }
        deleteAction(name) {
            if (typeof this.actions[name] === 'undefined') {
                return;
            }
            this.actions[name].actionEl.remove();
        }
        updateImage(data = {}) {
            this.fire('image.update', [this]);
            
            const formData = new FormData(this.formEl);
            const loadingEl = this.imgEl.closest('[data-image-loading]');
            loadingEl.classList.add('loading');
            
            for (var key in data) {
                formData.append(key, data[key]);
            }
            
            fetch(this.config.previewUrl, {
                method: this.config.previewMethod,
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                loadingEl.classList.remove('loading');
                this.imgEl.setAttribute('src', data.file.dataUrl);
                
                // update attributes:
                ['width', 'height', 'size', 'mimetype'].forEach(attr => {
                    const attrEl = this.formEl.querySelector('[data-image-attr="'+attr+'"]');
                    
                    if (attrEl) {
                        attrEl.textContent = data['file'][attr];
                    }
                });
                
                this.fire('image.updated', [this]);
            });
        }
        listen(eventName, callback) {
            this.events.listen(eventName, callback);
        }
        fire(eventName, parameters) {
            this.events.fire(eventName, parameters);
        }
    }
    
    class ImageAction {
        constructor(editor, actionEl) {
            this.editor = editor;
            this.actionEl = actionEl;
            this.name = actionEl.getAttribute('data-image-action');
            this.isOpen = false;
            this.canSave = true;
            
            ['click', 'input'].forEach(evt => {
                this.actionEl.addEventListener(evt, (e) => {
                    //e.preventDefault(); // input radio!
                    e.stopPropagation();

                    if (e.target.hasAttribute('data-image-action-trigger')) {
                        const triggers = e.target.getAttribute('data-image-action-trigger');

                        triggers.split('|').forEach(action => {
                            this.handleAction(action, e);
                        });
                    }
                });
            });
        }
        handleAction(action, e) {
            switch (action) {
                case 'save':
                    e.preventDefault();
                    this.editor.fire('action.save', [this, this.editor, e]);
                    if (this.canSave) {
                        this.editor.formEl.submit();
                    }
                    break;
                case 'reset':
                    this.editor.fire('action.reset', [this, this.editor, e]);
                    this.editor.formEl.reset();

                    Object.values(this.editor.actions).forEach(action => {
                        action.close();
                        action.reset();
                    });

                    this.editor.updateImage();
                    this.editor.fire('action.reseted', [this, this.editor, e]);
                    break;
                case 'toggle-open':
                    this.isOpen ? this.close() : this.open();
                    break;
                case 'toggle-disabled':
                    const fields = this.actionEl.querySelectorAll('input');
                    fields.forEach(el => {
                        if (el.hasAttribute('data-disable')) {
                            el.toggleAttribute('disabled');
                            this.editor.updateImage();                            
                        }
                    });
                    break;
                case 'toggle-disabled-checkbox':
                    if (e.type === 'input') { return; }
                    const el = this.actionEl.querySelector('[id="'+e.target.getAttribute('data-disable-checkbox')+'"]');
                    el.toggleAttribute('disabled');
                    break;
                case 'range':
                    const range = e.target.parentNode.querySelector('[data-range]');
                    range.textContent = e.target.value;
                    break;
            }
        }
        open() {
            if (this.isOpen) {
                return;
            }
            
            this.closeAll();
            this.editor.fire('action.open', [this, this.editor]);
            this.isOpen = true;
            this.actionEl.classList.add('active');
            this.editor.fire('action.opened', [this, this.editor]);
        }
        close() {
            if (this.isOpen) {
                this.editor.fire('action.close', [this, this.editor]);
                this.actionEl.classList.remove('active');
                this.isOpen = false;
                this.editor.fire('action.closed', [this, this.editor]);
            }
        }
        closeAll() {
            // do not close if parent has image action such as filters!
            if (this.actionEl.parentNode.closest('[data-image-action]')) {
                return;
            }
            
            Object.values(this.editor.actions).forEach(action => {
                if (action.actionEl.parentNode.closest('[data-image-action]') === null) {
                    action.close();
                }
            });
        }
        reset() {
            const ranges = this.actionEl.querySelectorAll('[data-range]');
            ranges.forEach(el => {
                el.textContent = el.getAttribute('data-range');
            });
            
            const fields = this.actionEl.querySelectorAll('input');
            fields.forEach(el => {
                if (el.hasAttribute('data-disable') && ! el.hasAttribute('disabled')) {
                    el.setAttribute('disabled', 'disabled');
                }
            });
            
            const checkboxes = this.editor.formEl.querySelectorAll('[data-disable-checkbox]');
            checkboxes.forEach(el => {
                const iEl = this.editor.formEl.querySelector('[id="'+el.getAttribute('data-disable-checkbox')+'"]');
                iEl.toggleAttribute('disabled');
            });
        }
    }

    const editors = {
        items: {},
        events: new Eventer(),
        register: function() {
            const editors = document.querySelectorAll('[data-image-editor]');
            
            editors.forEach(el => {
                const config = JSON.parse(el.getAttribute('data-image-editor'));
                
                if (typeof config['id'] === 'undefined') {
                    return;
                }
                
                el.removeAttribute('data-image-editor');
                el.setAttribute('data-image-editor-id', config['id']);
                
                if (el.tagName.toLowerCase() !== 'form') {
                    return;
                }
                
                this.set(config['id'], new ImageEditor(el, config));
            });
        },
        set: function(id, obj) {
            this.events.fire('editor.created', [obj]);
            this.items[id] = obj;
        },
        get: function(id) {
            return this.items[id];
        },
        all: function() {
            return this.items;
        },
        has: function(id) {
            return (typeof this.items[id] === 'undefined') ? false : true;
        },
        delete: function(id) {
            delete this.items[id];
        },
        create: function(el, config = {}) {
            if (!el) {
                return;
            }
            
            if (el.tagName.toLowerCase() !== 'form') {
                return;
            }
            
            if (typeof config['id'] === 'undefined') {
                return;
            }

            if (! this.has(config['id'])) {
                this.set(config['id'], new ImageEditor(el, config));
            }
            
            return this.items[config['id']];
        }
    };
    
    document.addEventListener('DOMContentLoaded', (e) => {
        editors.register();
    });
    
    return editors;
    
})(window, document);

export default imageEditors;