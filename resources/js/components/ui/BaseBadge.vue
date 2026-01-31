<template>
    <span :class="[
        'inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full ring-1 ring-inset',
        variantClasses
    ]">
        <slot></slot>
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: { type: String, default: 'gray' },
});

const variantClasses = computed(() => {
    const variants = {
        gray: 'bg-gray-50 text-gray-700 ring-gray-600/20',
        blue: 'bg-blue-50 text-blue-700 ring-blue-600/20',
        green: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        red: 'bg-red-50 text-red-700 ring-red-600/20',
        amber: 'bg-amber-50 text-amber-700 ring-amber-600/20',
        purple: 'bg-purple-50 text-purple-700 ring-purple-600/20',
    };
    return variants[props.variant];
});
</script>