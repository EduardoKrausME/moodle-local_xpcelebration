// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * celebration.js
 *
 * @package   local_xpcelebration
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/ajax', 'core/templates', 'core/notification', 'core/str'], function(Ajax, Templates, Notification, Str) {
    'use strict';

    var state = {
        courseid: 0,
        config: {},
        userReduced: false,
        labels: null,
        stopped: false
    };

    var wait = function(milliseconds) {
        return new Promise(function(resolve) {
            window.setTimeout(resolve, milliseconds);
        });
    };

    var systemReducedMotion = function() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    };

    var reducedMotion = function() {
        return state.userReduced || systemReducedMotion();
    };

    var loadLabels = function() {
        if (state.labels) {
            return Promise.resolve(state.labels);
        }
        return Promise.all([
            Str.get_string('continue', 'local_xpcelebration'),
            Str.get_string('dismiss', 'local_xpcelebration'),
            Str.get_string('reduceanimations', 'local_xpcelebration'),
            Str.get_string('enableanimations', 'local_xpcelebration')
        ]).then(function(values) {
            state.labels = {
                continueLabel: values[0],
                dismissLabel: values[1],
                reduceLabel: values[2],
                enableLabel: values[3]
            };
            return state.labels;
        });
    };

    var claimOne = function() {
        return Ajax.call([{
            methodname: 'local_xpcelebration_claim_pending',
            args: {courseid: state.courseid, limit: 1}
        }])[0];
    };

    var saveReducedMotion = function(enabled) {
        return Ajax.call([{
            methodname: 'local_xpcelebration_set_reduced_motion',
            args: {enabled: enabled ? 1 : 0}
        }])[0];
    };

    var templateFor = function(display) {
        if (display === 'toast') {
            return 'local_xpcelebration/toast';
        }
        if (display === 'modal') {
            return 'local_xpcelebration/modal';
        }
        return 'local_xpcelebration/achievement_card';
    };

    var particleCount = function(kind) {
        var intensity = state.config.intensity || 'medium';
        var counts = {
            confetti: {low: 14, medium: 26, high: 42},
            particles: {low: 7, medium: 12, high: 20}
        };
        return counts[kind][intensity] || counts[kind].medium;
    };

    var createConfetti = function() {
        var layer = document.createElement('div');
        layer.className = 'local-xpcelebration__particle-layer';
        var colors = ['#3158d4', '#7c3aed', '#0f766e', '#b45309', '#dc2626'];
        var count = particleCount('confetti');
        for (var i = 0; i < count; i++) {
            var particle = document.createElement('span');
            particle.className = 'local-xpcelebration__particle';
            particle.style.setProperty('--x', Math.round(Math.random() * 100) + '%');
            particle.style.setProperty('--size', (4 + Math.round(Math.random() * 5)) + 'px');
            particle.style.setProperty('--delay', (Math.random() * 0.35) + 's');
            particle.style.setProperty('--duration', (1.3 + Math.random() * 1.2) + 's');
            particle.style.setProperty('--drift', (-80 + Math.random() * 160) + 'px');
            particle.style.setProperty('--rotation', (-540 + Math.random() * 1080) + 'deg');
            particle.style.setProperty('--particle', colors[i % colors.length]);
            particle.style.setProperty('--radius', i % 3 === 0 ? '999px' : '2px');
            layer.appendChild(particle);
        }
        document.body.appendChild(layer);
        window.setTimeout(function() { layer.remove(); }, 3200);
    };

    var createParticles = function() {
        var layer = document.createElement('div');
        layer.className = 'local-xpcelebration__particle-layer';
        var count = particleCount('particles');
        for (var i = 0; i < count; i++) {
            var angle = (Math.PI * 2 * i) / count;
            var distance = 55 + Math.random() * 90;
            var particle = document.createElement('span');
            particle.className = 'local-xpcelebration__particle local-xpcelebration__particle--radial';
            particle.style.setProperty('--dx', Math.cos(angle) * distance + 'px');
            particle.style.setProperty('--dy', Math.sin(angle) * distance + 'px');
            particle.style.setProperty('--delay', (Math.random() * 0.12) + 's');
            particle.style.setProperty('--particle', 'var(--xp-accent, #3158d4)');
            layer.appendChild(particle);
        }
        document.body.appendChild(layer);
        window.setTimeout(function() { layer.remove(); }, 1500);
    };

    var playSound = function() {
        if (!state.config.sound) {
            return;
        }
        try {
            var AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) {
                return;
            }
            var context = new AudioContext();
            var gain = context.createGain();
            gain.gain.value = 0.035;
            gain.connect(context.destination);
            [523.25, 659.25].forEach(function(frequency, index) {
                var oscillator = context.createOscillator();
                oscillator.frequency.value = frequency;
                oscillator.type = 'sine';
                oscillator.connect(gain);
                oscillator.start(context.currentTime + (index * 0.08));
                oscillator.stop(context.currentTime + 0.18 + (index * 0.08));
            });
            window.setTimeout(function() { context.close(); }, 500);
        } catch (error) {
            // Browsers may block audio without user interaction. Visual feedback still works.
        }
    };

    var applyAnimation = function(node, item) {
        if (reducedMotion() || item.animation === 'none') {
            return;
        }
        var animation = item.animation;
        if (animation === 'confetti' && !state.config.confetti) {
            animation = 'badge';
        }
        if (animation === 'confetti') {
            createConfetti();
        } else if (animation === 'particles') {
            createParticles();
        } else if (animation === 'glow') {
            node.classList.add('is-glowing');
        } else if (animation === 'badge') {
            node.classList.add('is-badge-growing');
        } else if (animation === 'progress') {
            var bar = node.querySelector('.local-xpcelebration__progressbar');
            if (bar) {
                window.requestAnimationFrame(function() {
                    bar.style.width = Math.max(0, Math.min(100, item.progress)) + '%';
                });
            }
        }
    };

    var renderItem = function(item, labels) {
        var context = Object.assign({}, item, {
            continueLabel: labels.continueLabel,
            dismissLabel: labels.dismissLabel,
            motionLabel: state.userReduced ? labels.enableLabel : labels.reduceLabel
        });

        return new Promise(function(resolve, reject) {
            Templates.render(templateFor(item.display), context).then(function(html, js) {
                var holder = document.createElement('div');
                holder.innerHTML = html.trim();
                var node = holder.firstElementChild;
                if (!node) {
                    reject(new Error('XP Celebration template returned no root node.'));
                    return;
                }
                document.body.appendChild(node);
                Templates.runTemplateJS(js);

                var finished = false;
                var finish = function() {
                    if (finished) {
                        return;
                    }
                    finished = true;
                    node.remove();
                    resolve();
                };

                var close = node.querySelector('[data-action="close"]');
                if (close) {
                    close.addEventListener('click', finish);
                    close.focus({preventScroll: true});
                }

                var motion = node.querySelector('[data-action="motion"]');
                if (motion) {
                    motion.addEventListener('click', function() {
                        var next = !state.userReduced;
                        state.userReduced = next;
                        motion.textContent = next ? labels.enableLabel : labels.reduceLabel;
                        saveReducedMotion(next).catch(Notification.exception);
                    });
                }

                applyAnimation(node, item);
                playSound();
                window.setTimeout(finish, state.config.duration || 4500);
            }).catch(reject);
        });
    };

    var processQueue = function(labels) {
        if (state.stopped) {
            return Promise.resolve();
        }
        return claimOne().then(function(items) {
            if (!items || items.length === 0) {
                return null;
            }
            return renderItem(items[0], labels)
                .then(function() { return wait(state.config.interval || 900); })
                .then(function() { return processQueue(labels); });
        });
    };

    var start = function() {
        loadLabels().then(processQueue).catch(function(error) {
            Notification.exception(error);
        });
    };

    var init = function(courseid, config) {
        state.courseid = Number(courseid) || 0;
        state.config = config || {};
        state.userReduced = Boolean(state.config.reducedmotion);
        state.stopped = false;
        if (!state.courseid) {
            return;
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', start, {once: true});
        } else {
            start();
        }
    };

    return {init: init};
});
