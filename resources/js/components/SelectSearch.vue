<script setup lang="ts">
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Combobox, ComboboxAnchor, ComboboxEmpty, ComboboxGroup, ComboboxInput, ComboboxItem, ComboboxItemIndicator, ComboboxList, ComboboxTrigger } from '@/components/ui/combobox'
import { Check, ChevronsUpDown, Search } from 'lucide-vue-next'
import { onMounted, ref, watch } from 'vue'
import { InertiaForm } from '@inertiajs/vue3'

export type OptionType = {
  value: string | number
  label: string
}

interface Props {
    options: OptionType[]
    name?: string
    placeholder?: string
    form?: InertiaForm<any>
    isMultiple?: boolean
    defaultValue?: number | number[] | string | string[]
}

const props = defineProps<Props>()
const emit = defineEmits(['change'])
const value = ref<OptionType | OptionType[]>()

watch(() => value.value, (newValue) => {
    if (props?.name) {
      if (props.form?.[props.name]) {
          if (Array.isArray(newValue)) {
              props.form[props.name] = newValue.map((option) => option.value)
          } else {
              props.form[props.name] = newValue?.value
          }
      } else {
          if (Array.isArray(newValue)) {
              props.form[props.name] = newValue.map((option) => option.value)
          } else {
              props.form[props.name] = newValue?.value
          }
      }
    } else {
      emit('change', newValue)
    }
    
})

onMounted(() => {
    if (props.name) {
      if (props.form?.[props.name]) {
          if (props.isMultiple) {
              value.value = props.options.filter((option) => props.form?.[props.name as string].includes(option.value))
          } else {
              value.value = props.options.find((option) => option.value === props.form?.[props.name as string])
              
          }
      } else {
          value.value = undefined
      }
    } else if (props.defaultValue) {
      if (Array.isArray(props.defaultValue)) {
          value.value = props.options.filter((option) => (props.defaultValue as (string | number)[]).includes(option.value))
      } else {
          value.value = props.options.find((option) => option.value === props.defaultValue)
      }
    } else {
      value.value = undefined
    }
})
</script>

<template>
  <Combobox v-model="value" by="label" :multiple="props.isMultiple">
    <ComboboxAnchor as-child>
      <ComboboxTrigger as-child>
        <Button variant="outline" class="justify-between">
          {{ value ? (Array.isArray(value) ? `(${value.length}) Selected` : value?.label) : (props.placeholder || 'Select Option') }}
          <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </ComboboxTrigger>
    </ComboboxAnchor>

    <ComboboxList>
      <div class="relative w-full max-w-sm items-center">
        <ComboboxInput class="pl-9 focus-visible:ring-0 border-0 border-b rounded-none h-10" :placeholder="placeholder || 'Select Option'" />
        <span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
          <Search class="size-4 text-muted-foreground" />
        </span>
      </div>

      <ComboboxEmpty>
        No Option found.
      </ComboboxEmpty>

      <ComboboxGroup>
        <ComboboxItem
          v-for="option in options"
          :key="option.value"
          :value="option"
        >
          {{ option.label }}

          <ComboboxItemIndicator>
            <Check :class="cn('ml-auto h-4 w-4')" />
          </ComboboxItemIndicator>
        </ComboboxItem>
      </ComboboxGroup>
    </ComboboxList>
  </Combobox>
</template>