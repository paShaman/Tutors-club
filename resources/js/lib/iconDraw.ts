import type { Directive } from 'vue'

const SHAPES = 'path, circle, line, polyline, polygon, rect'

/**
 * Проставляет контурам иконки pathLength="1", чтобы CSS-анимация отрисовки
 * (stroke-dasharray / stroke-dashoffset) не зависела от реальной длины линий.
 * Использование: <component :is="icon" v-icon-draw class="nav-icon-draw" />
 */
export const vIconDraw: Directive<SVGElement> = {
  mounted(el) {
    el.querySelectorAll<SVGGeometryElement>(SHAPES).forEach((shape) => {
      shape.setAttribute('pathLength', '1')
    })
  },
}
