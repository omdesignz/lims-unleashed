<script setup>
import { code128Svg, fillLabelPlaceholders, qrCodeSvg } from '@/Support/label-codes.mjs'
import { computed } from 'vue'

/**
 * A label as it prints: its size in millimetres, the text centred in a 2 mm
 * margin, the QR code at the top left, the logo at the top right and the
 * barcode at the bottom right (or where the label places them), at a chosen
 * scale. It follows the print sheet (`PDFs/labels/chrome-sheet`).
 */
const props = defineProps({
  label: { type: Object, required: true },
  /** Values for the {placeholders} of the text and the codes. */
  values: { type: Object, default: () => ({}) },
  /** Screen pixels per millimetre; 3.78 is the label's real size on a 96 dpi screen. */
  scale: { type: Number, default: 3.78 },
  /** Accessible name; the drawing itself is decorative. */
  title: { type: String, default: '' },
  /** Shown faintly while the label has no text; never printed. */
  emptyText: { type: String, default: '' },
})

const REAL_PX_PER_MM = 96 / 25.4

const millimetres = (value, fallback) => {
  const number = Number(value)

  return Number.isFinite(number) && number > 0 ? number : fallback
}

const width = computed(() => millimetres(props.label.width, 50))
const height = computed(() => millimetres(props.label.height, 25))
const px = (mm) => `${(mm * props.scale).toFixed(2)}px`

// Text and border are set in print pixels; they shrink and grow with the label.
const zoom = computed(() => props.scale / REAL_PX_PER_MM)

const frameStyle = computed(() => ({
  width: px(width.value),
  height: px(height.value),
  background: props.label.background_color || '#ffffff',
  color: props.label.text_color || '#000000',
  border: Number(props.label.border_width) > 0
    ? `${Math.max(1, Number(props.label.border_width) * zoom.value).toFixed(2)}px solid ${props.label.border_color || '#000000'}`
    : 'none',
}))

const textStyle = computed(() => ({
  padding: px(2),
  fontSize: `${(millimetres(props.label.font_size, 12) * zoom.value).toFixed(2)}px`,
  justifyContent: { left: 'flex-start', right: 'flex-end', justify: 'stretch' }[props.label.text_alignment] || 'center',
  textAlign: props.label.text_alignment || 'center',
}))

const text = computed(() => fillLabelPlaceholders(props.label.content || '', props.values))

function place(position, fallback, size) {
  const style = { width: px(size[0]), height: px(size[1]) }

  if (position && Number.isFinite(Number(position.top)) && Number.isFinite(Number(position.left))) {
    return { ...style, top: px(Number(position.top)), left: px(Number(position.left)) }
  }

  return { ...style, ...Object.fromEntries(Object.entries(fallback).map(([side, mm]) => [side, px(mm)])) }
}

const qrContent = computed(() => fillLabelPlaceholders(props.label.qr_code_content || '{qr_content}', props.values))
const qrMarkup = computed(() => (props.label.has_qr_code && !/\{[a-z_]+\}/.test(qrContent.value)
  ? qrCodeSvg(qrContent.value, { color: props.label.text_color || '#000000' })
  : null))
const qrSize = computed(() => millimetres(props.label.qr_code_size, 10))
const qrStyle = computed(() => place(props.label.qr_code_position, { top: 2, left: 2 }, [qrSize.value, qrSize.value]))

const barcodeContent = computed(() => fillLabelPlaceholders(props.label.barcode_content || '{barcode_content}', props.values))
const barcodeMarkup = computed(() => (props.label.has_barcode && !/\{[a-z_]+\}/.test(barcodeContent.value)
  ? code128Svg(barcodeContent.value, { color: props.label.text_color || '#000000' })
  : null))
const barcodeStyle = computed(() => place(
  props.label.barcode_position,
  { right: 2, bottom: 2 },
  [millimetres(props.label.barcode_width, 30), millimetres(props.label.barcode_height, 10)],
))

const logoSize = computed(() => millimetres(props.label.logo_size, 15))
const logoStyle = computed(() => place(props.label.logo_position, { top: 2, right: 2 }, [logoSize.value, logoSize.value]))
</script>

<template>
  <div class="label-preview" :style="frameStyle" role="img" :aria-label="title || `Etiqueta ${width} × ${height} mm`">
    <div class="label-preview-text" :style="textStyle">
      <template v-if="text">{{ text }}</template>
      <span v-else-if="emptyText" class="label-preview-empty">{{ emptyText }}</span>
    </div>
    <img v-if="label.logo_path" class="label-preview-part" :style="logoStyle" :src="label.logo_path" alt="">
    <div v-if="label.has_qr_code" class="label-preview-part" :style="qrStyle">
      <div v-if="qrMarkup" class="label-preview-code" v-html="qrMarkup" />
      <span v-else class="label-preview-missing">QR</span>
    </div>
    <div v-if="label.has_barcode" class="label-preview-part" :style="barcodeStyle">
      <div v-if="barcodeMarkup" class="label-preview-code" v-html="barcodeMarkup" />
      <span v-else class="label-preview-missing">{{ barcodeContent }}</span>
    </div>
  </div>
</template>
