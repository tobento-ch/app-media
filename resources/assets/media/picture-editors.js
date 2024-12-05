import imageEditors from './image-editors.js';

const pictureEditors = (function(window, document) {
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
    
    class PictureEditor {
        constructor(formEl, config) {
            this.formEl = formEl;
            this.config = config;
            this.imageEditors = [];
            this.canSave = true;
            this.events = new Eventer();
            
            if (typeof this.config['createImageEditors'] !== 'undefined') {
                this.createImageEditors();
            }
            
            // actions:
            this.actions = {};
            this.formEl.querySelectorAll('[data-picture-editor-action]').forEach(el => {
                this.registerAction(el, el.getAttribute('data-picture-editor-action'));
            });
        }
        registerAction(el, name) {
            el.addEventListener('click', (e) => {
                this.handleAction(name, e);
            });
            
            this.actions[el.getAttribute('data-picture-editor-action')] = el;
        }
        deleteAction(name) {
            if (typeof this.actions[name] === 'undefined') {
                return;
            }
            this.actions[name].remove();
        }
        handleAction(action, e) {
            switch (action) {
                case 'save':
                    e.preventDefault();
                    
                    const forms = document.querySelectorAll('[data-picture-editor-images="'+this.config.id+'"] form');
                    const formData = new FormData(this.formEl);
                    
                    forms.forEach(form => {
                        const fd = new FormData(form);
                        const formName = form.getAttribute('name');
                        
                        for (const pair of fd.entries()) {
                            const input = document.createElement('input');
                            input.setAttribute('type', 'hidden');
                            input.setAttribute('name', this.toInputName(this.toDotNotation(formName+'.'+pair[0])));
                            input.setAttribute('value', pair[1]);
                            this.formEl.append(input);
                        }
                    });
                    
                    this.fire('action.save', [this, e]);
                    
                    if (this.canSave) {
                        this.formEl.submit();
                    }
                    break;
            }
        }
        createImageEditors() {
            const forms = document.querySelectorAll('[data-picture-editor-images="'+this.config.id+'"] form');
            
            forms.forEach(form => {
                const config = JSON.parse(form.getAttribute('data-image-editor'));
                const editor = imageEditors.create(form, config);
                this.imageEditors.push(editor);
            });
        }
        listen(eventName, callback) {
            this.events.listen(eventName, callback);
        }
        fire(eventName, parameters) {
            this.events.fire(eventName, parameters);
        }
        toDotNotation(string) {
            return string.replaceAll('[]', '').replaceAll('[', '.').replaceAll(']', '');
        }
        toInputName(string) {
            const segments = string.split('.');
            let name = segments[0];
            
            delete segments[0];
            
            segments.forEach(segment => {
                name += '['+segment+']';
            });
            
            return name;
        }
    }

    const editors = {
        items: {},
        register: function() {
            const editors = document.querySelectorAll('[data-picture-editor]');
            
            editors.forEach(el => {
                const config = JSON.parse(el.getAttribute('data-picture-editor'));
                
                if (typeof config['id'] === 'undefined') {
                    return;
                }
                
                el.removeAttribute('data-picture-editor');
                el.setAttribute('data-picture-editor-id', config['id']);
                
                if (el.tagName.toLowerCase() !== 'form') {
                    return;
                }
                
                this.set(config['id'], new PictureEditor(el, config));
            });
        },
        set: function(id, obj) {
            this.items[id] = obj;
        },
        get: function(id) {
            return this.items[id];
        },
        has: function(id) {
            return (typeof this.items[id] === 'undefined') ? false : true;
        },
        delete: function(id) {
            if (this.has(id) === false) {
                return;
            }
            
            this.get(id).imageEditors.forEach(editor => {
                imageEditors.delete(editor.id);
            });
            
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
                this.set(config['id'], new PictureEditor(el, config));
            }
            
            return this.items[config['id']];
        }
    };
    
    document.addEventListener('DOMContentLoaded', (e) => {
        editors.register();
    });
    
    return editors;
    
})(window, document);

export default pictureEditors;