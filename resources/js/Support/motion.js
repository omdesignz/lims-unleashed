/**
 * Shared motion language for the application, built on Motion (motion.dev).
 *
 * Interface motion is crisp and quiet: entrances ease out, exits are shorter
 * than entrances, springs never bounce, and keyboard-driven actions do not
 * animate at all.
 */
export const easeOut = [0.23, 1, 0.32, 1]
export const easeDrawer = [0.32, 0.72, 0, 1]

export const springSnappy = { type: 'spring', duration: 0.3, bounce: 0 }

export const durations = {
  press: 0.15,
  popover: 0.16,
  page: 0.2,
  reveal: 0.36,
}

export function prefersReducedMotion() {
  return typeof window !== 'undefined'
    && typeof window.matchMedia === 'function'
    && window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

/**
 * Entrances are skipped when they could not be seen — reduced motion, or a tab
 * that loads in the background — so content is never left waiting on a frame.
 */
export function shouldSkipEntrance() {
  return prefersReducedMotion() || (typeof document !== 'undefined' && document.visibilityState !== 'visible')
}
