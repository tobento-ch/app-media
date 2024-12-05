import cropper from './../js-cropper/cropper.js';
import imageEditors from './image-editors.js';

const imageCrop = (function(window, document) {
    'use strict';
    
    class ImageCrop {
        constructor(editor) {
            this.editor = editor;
            this.cropData = {width: null, height: null, x: null, y: null, scale: 1};
            this.target = [];
            this.inputs = [];
            this.disabled = false;
            this.initCropData();
            this.handleTarget();
            
            if (cropper.has(this.editor.id)) {
                cropper.get(this.editor.id).destroy();
            }
            
            editor.listen('action.open', (action) => {
                if (action.name === 'crop') {
                    this.crop(this.target);
                }
            });

            editor.listen('action.close', (action) => {
                if (cropper.has(this.editor.id)) {
                    this.uncrop({width: this.target[0], height: this.target[1]});
                }
            });
            
            editor.listen('action.save', (action) => {
                if (cropper.has(this.editor.id)) {
                    this.uncrop({width: this.target[0], height: this.target[1]}, false);
                }
            });
            
            editor.listen('action.reset', (action) => {
                this.inputs.forEach(el => {
                    el.remove();
                });
            });
        }
        initCropData() {
            const el = this.editor.formEl.querySelector('[data-image-crop]');

            if (!el) {
                return;
            }
            
            const data = JSON.parse(el.getAttribute('data-image-crop'));
            
            if (typeof data['target'] !== 'undefined') {
                this.target = data['target'];
            }
            
            if (typeof data['crop'] !== 'undefined') {
                this.cropData = data['crop'];
            }
            
            if (typeof data['disabled'] !== 'undefined') {
                this.disabled = data['disabled'];
            }
        }
        handleTarget() {
            const widthEl = this.editor.formEl.querySelector('input[name="target_width"]');
            const heightEl = this.editor.formEl.querySelector('input[name="target_height"]');
            
            if (widthEl && heightEl) {
                if (typeof this.target[0] !== 'undefined') {
                    widthEl.setAttribute('value', this.target[0]);
                }
                
                widthEl.addEventListener('keyup', (e) => {
                    this.updateCropTarget(widthEl, heightEl);
                });
                
                if (typeof this.target[1] !== 'undefined') {
                    heightEl.setAttribute('value', this.target[1]);
                }
                
                heightEl.addEventListener('keyup', (e) => {
                    this.updateCropTarget(widthEl, heightEl);
                });
                
                if (this.disabled) {
                    widthEl.setAttribute('disabled', 'disabled');
                    heightEl.setAttribute('disabled', 'disabled');
                }
            }
        }
        updateCropTarget(widthEl, heightEl) {
            this.target = [widthEl.value, heightEl.value];
            this.uncrop({width: widthEl.value, height: heightEl.value}, false);
            this.crop([widthEl.value, heightEl.value]);
        }
        crop(target = []) {
            this.editor.imgEl.setAttribute('src', this.editor.imgSrc);
            
            const crop = cropper.create(this.editor.imgEl, {
                id: this.editor.id,
                target: target,
                crop: {
                    x: this.cropData.x,
                    y: this.cropData.y,
                    width: this.cropData.width,
                    height: this.cropData.height,
                    scale: this.cropData.scale,
                },
                //keep_ratio: false
            });
            
            setTimeout(() => {
                this.applyInputs(crop.data(), {width: target[0], height: target[1]});
            }, 100);
            
            crop.listen('stopped', (event, crop) => {
                this.applyInputs(crop.data(), {width: target[0], height: target[1]});
            });
        }
        uncrop(target = {}, updateImage = true) {
            const crop = cropper.get(this.editor.id);
            
            if (crop) {
                this.cropData = crop.data();
            }
            
            this.applyInputs(this.cropData, target);
            
            if (updateImage) {
                this.editor.updateImage();
            }
            
            if (crop) {
                crop.destroy();
            }
        }
        applyInputs(crop, target = {}) {
            ['width', 'height', 'x', 'y', 'scale'].forEach(attr => {
                let el = this.editor.formEl.querySelector('input[name="actions[crop]['+attr+']"]');
                
                if (!el) {
                    el = document.createElement('input');
                    el.setAttribute('type', 'hidden');
                    el.setAttribute('name', 'actions[crop]['+attr+']');
                    this.editor.formEl.appendChild(el);
                    this.inputs.push(el);
                }

                el.setAttribute('value', crop[attr]);
            });
            
            let mode = 'resize';
            
            if (
                typeof target['width'] !== 'undefined'
                && target['width'] !== ''
                && typeof target['height'] !== 'undefined'
                && target['height'] !== ''
            ) {
                mode = 'fit';
            }
            
            ['width', 'height'].forEach(attr => {
                let el = this.editor.formEl.querySelector('input[name="actions['+mode+']['+attr+']"]');
                
                if (!el) {
                    el = document.createElement('input');
                    el.setAttribute('type', 'hidden');
                    el.setAttribute('name', 'actions['+mode+']['+attr+']');
                    this.editor.formEl.appendChild(el);
                    this.inputs.push(el);
                }
                
                if (typeof target[attr] === 'undefined' || target[attr] === '') {
                    el.remove();
                    return;
                }
                
                el.setAttribute('value', target[attr]);
            });
        }
    }
    
    document.addEventListener('DOMContentLoaded', (e) => {
        Object.values(imageEditors.all()).forEach(editor => {
            new ImageCrop(editor);
        });

        imageEditors.events.listen('editor.created', (editor) => {
            new ImageCrop(editor);
        });
    });
    
})(window, document);

export default imageCrop;