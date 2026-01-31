<template>
    <div :class="[
        'bg-white rounded-2xl border border-gray-100 overflow-hidden',
        shadow ? 'shadow-lg shadow-gray-200/50' : '',
        hover ? 'hover:shadow-xl transition-shadow duration-300' : ''
    ]">
        <!-- Header -->
        <div v-if="title || $slots.header" class="px-6 py-4 border-b border-gray-100">
            <slot name="header">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div v-if="icon" :class="[
                            'w-10 h-10 rounded-xl flex items-center justify-center shadow-lg',
                            iconBgClass
                        ]">
                            <component :is="icon" class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ title }}</h3>
                            <p v-if="subtitle" class="text-sm text-gray-500">{{ subtitle }}</p>
                        </div>
                    </div>
                    <slot name="header-actions"></slot>
                </div>
            </slot>
        </div>

        <!-- Body -->
        <div :class="padding ? 'p-6' : ''">
            <slot></slot>
        </div>

        <!-- Footer -->
        <div v-if="$slots.footer" class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
            <slot name="footer"></slot>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: String,
    subtitle: String,
    icon: Object,
    iconColor: { type: String, default: 'blue' },
    shadow: { type: Boolean, default: true },
    hover: { type: Boolean, default: false },
    padding: { type: Boolean, default: true },
});

const iconBgClass = computed(() => {
    const colors = {
        blue: 'bg-gradient-to-br from-blue-500 to-indigo-600 shadow-blue-500/30',
        green: 'bg-gradient-to-br from-emerald-500 to-green-600 shadow-emerald-500/30',
        red: 'bg-gradient-to-br from-red-500 to-rose-600 shadow-red-500/30',
        amber: 'bg-gradient-to-br from-amber-500 to-orange-600 shadow-amber-500/30',
        purple: 'bg-gradient-to-br from-purple-500 to-violet-600 shadow-purple-500/30',
    };
    return colors[props.iconColor];
});
</script>