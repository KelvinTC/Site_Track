<template>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-gray-100">
                    <th
                        v-for="column in columns"
                        :key="column.key"
                        :class="[
                            'px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider',
                            column.align === 'right' ? 'text-right' : 'text-left'
                        ]"
                    >
                        {{ column.label }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <tr v-if="loading">
                    <td :colspan="columns.length" class="px-4 py-12 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                            <p class="text-gray-500">Loading...</p>
                        </div>
                    </td>
                </tr>
                <tr v-else-if="data.length === 0">
                    <td :colspan="columns.length" class="px-4 py-12 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                            </div>
                            <p class="text-gray-500 font-medium">{{ emptyMessage }}</p>
                        </div>
                    </td>
                </tr>
                <tr
                    v-else
                    v-for="(row, index) in data"
                    :key="row.id || index"
                    class="hover:bg-gray-50/50 transition-colors"
                >
                    <slot :row="row" :index="index"></slot>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
defineProps({
    columns: { type: Array, required: true },
    data: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    emptyMessage: { type: String, default: 'No data available' },
});
</script>