import {
  useEffect,
  useRef,
  useState,
  type CSSProperties,
  type ReactNode,
} from "react"

export const prefersReducedMotion = () =>
  typeof window !== "undefined" &&
  window.matchMedia?.("(prefers-reduced-motion: reduce)").matches === true

// Menandai elemen ketika pertama kali masuk viewport (sekali saja).
export function useInView<T extends Element>() {
  const ref = useRef<T>(null)
  const [inView, setInView] = useState(false)
  useEffect(() => {
    const el = ref.current
    if (!el) return
    if (prefersReducedMotion() || typeof IntersectionObserver === "undefined") {
      setInView(true)
      return
    }
    const io = new IntersectionObserver(
      ([entry]) => {
        if (entry?.isIntersecting) {
          setInView(true)
          io.disconnect()
        }
      },
      { rootMargin: "0px 0px -8% 0px" },
    )
    io.observe(el)
    return () => io.disconnect()
  }, [])
  return [ref, inView] as const
}

type RevealProps = {
  children: ReactNode
  className?: string
  /** Animasikan setiap anak langsung secara bergiliran (untuk grid kartu). */
  stagger?: boolean
  delay?: number
}

// Pembungkus ringan untuk animasi muncul saat di-scroll; gaya ada di index.css.
export default function Reveal({
  children,
  className = "",
  stagger,
  delay = 0,
}: RevealProps) {
  const [ref, inView] = useInView<HTMLDivElement>()
  return (
    <div
      ref={ref}
      className={`${stagger ? "reveal-stagger" : "reveal"} ${
        inView ? "is-in" : ""
      } ${className}`}
      style={
        delay ? { "--reveal-delay": `${delay}ms` } as CSSProperties : undefined
      }
    >
      {children}
    </div>
  )
}
