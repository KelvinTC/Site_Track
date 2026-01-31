<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeOnBackdrop && $emit('close')"></div>

                <!-- Modal Container -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4"
                    >
                        <div
                            v-if="show"
                            :class="[
                                'relative bg-white rounded-2xl shadow-2xl w-full transform',
                                sizeClasses
                            ]"
                        >
                            <!-- Header -->
                            <div v-if="title || $slots.header" class="px-6 py-4 border-b border-gray-100">
                                <slot name="header">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-lg font-bold text-gray-900">{{ title }}</h3>
                                        <button
                                            @click="$emit('close')"
                                            class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </slot>
                            </div>

                            <!-- Body -->
                            <div class="px-6 py-4">
                                <slot></slot>
                            </div>

                            <!-- Footer -->
                            <div v-if="$slots.footer" class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 rounded-b-2xl">
                                <slot name="footer"></slot>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: String,
    size: { type: String, default: 'md' },
    closeOnBackdrop: { type: Boolean, default: true },
});

defineEmits(['close']);

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'max-w-md',
        md: 'max-w-lg',
        lg: 'max-w-2xl',
        xl: 'max-w-4xl',
        full: 'max-w-6xl',
    };
    return sizes[props.size];
});

watch(() => props.show, (show) => {
    if (show) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});
</script>