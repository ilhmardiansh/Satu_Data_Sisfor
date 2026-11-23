import { useEffect, useRef, useState } from "react"
import { prefersReducedMotion, useInView } from "./Reveal"

type CountUpProps = {
  value: number | string
  duration?: number
  className?: string
}

// Angka ringkasan yang menghitung naik saat pertama terlihat. Nilai non-angka
// (mis. "Unggul") ditampilkan apa adanya.
export default function CountUp({
  value,
  duration = 1200,
  className = "",
}: CountUpProps) {
  const target = typeof value === "number" ? value : Number(value)
  const numeric = String(value).trim() !== "" && Number.isFinite(target)
  const [ref, inView] = useInView<HTMLSpanElement>()
  const [shown, setShown] = useState(() =>
    numeric && prefersReducedMotion() ? target : 0,
  )
  const current = useRef(shown)

  useEffect(() => {
    if (!numeric || !inView) return
    const from = current.current
    if (prefersReducedMotion() || from === target) {
      current.current = target
      setShown(target)
      return
    }
    let frame = 0
    const start = performance.now()
    const step = (now: number) => {
      const p = Math.min(1, (now - start) / duration)
      const next = Math.round(from + (target - from) * (1 - (1 - p) ** 3))
      current.current = next
      setShown(next)
      if (p < 1) frame = requestAnimationFrame(step)
    }
    frame = requestAnimationFrame(step)
    return () => cancelAnimationFrame(frame)
  }, [inView, numeric, target, duration])

  return (
    <span ref={ref} className={`tabular-nums ${className}`}>
      {numeric ? (
        <>
          <span aria-hidden="true">{shown}</span>
          <span className="sr-only">{target}</span>
        </>
      ) : (
        value
      )}
    </span>
  )
}
