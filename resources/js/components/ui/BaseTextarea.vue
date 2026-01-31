<template>
    <div>
        <label v-if="label" :for="id" class="block text-sm font-medium text-gray-700 mb-1.5">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <textarea
            :id="id"
            :value="modelValue"
            :placeholder="placeholder"
            :required="required"
            :disabled="disabled"
            :rows="rows"
            @input="$emit('update:modelValue', $event.target.value)"
            :class="[
                'block w-full rounded-xl border bg-gray-50 text-gray-900 placeholder-gray-400 transition-all duration-200',
                'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white',
                'px-4 py-2.5 resize-none',
                error ? 'border-red-300 bg-red-50' : 'border-gray-200',
                disabled ? 'opacity-50 cursor-not-allowed' : ''
            ]"
        ></textarea>
        <p v-if="error" class="mt-1.5 text-sm text-red-600">{{ error }}</p>
    </div>
</template>

<script setup>
defineProps({
    id: String,
    label: String,
    modelValue: String,
    placeholder: String,
    required: Boolean,
    disabled: Boolean,
    error: String,
    rows: { type: Number, default: 3 },
});

defineEmits(['update:modelValue']);
</script>