<template>
    <div>
        <label v-if="label" :for="id" class="block text-sm font-medium text-gray-700 mb-1.5">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <div class="relative">
            <div v-if="icon" class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <component :is="icon" class="w-5 h-5 text-gray-400" />
            </div>
            <input
                :id="id"
                :type="type"
                :value="modelValue"
                :placeholder="placeholder"
                :required="required"
                :disabled="disabled"
                @input="$emit('update:modelValue', $event.target.value)"
                :class="[
                    'block w-full rounded-xl border bg-gray-50 text-gray-900 placeholder-gray-400 transition-all duration-200',
                    'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white',
                    icon ? 'pl-11 pr-4' : 'px-4',
                    'py-2.5',
                    error ? 'border-red-300 bg-red-50' : 'border-gray-200',
                    disabled ? 'opacity-50 cursor-not-allowed' : ''
                ]"
            />
        </div>
        <p v-if="error" class="mt-1.5 text-sm text-red-600">{{ error }}</p>
        <p v-if="hint && !error" class="mt-1.5 text-sm text-gray-500">{{ hint }}</p>
    </div>
</template>

<script setup>
defineProps({
    id: String,
    label: String,
    type: { type: String, default: 'text' },
    modelValue: [String, Number],
    placeholder: String,
    required: Boolean,
    disabled: Boolean,
    error: String,
    hint: String,
    icon: Object,
});

defineEmits(['update:modelValue']);
</script>
