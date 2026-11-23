import {
  Bar,
  BarChart,
  Cell,
  Line,
  LineChart,
  Pie,
  PieChart,
  PolarAngleAxis,
  PolarGrid,
  Radar,
  RadarChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts"
import type { Kind } from "./store"

export type Item = {
  n: string
  v: number
}

export const palettes: Record<string, string[]> = {
  sky: ["#4472d0", "#0ea5e9", "#a0bbea", "#0f172a", "#7dd3fc", "#94a3b8"],
  blue: ["#3760bb", "#7398dd", "#a0bbea", "#1c2a4a", "#c7d6f3", "#94a3b8"],
  indigo: ["#4f46e5", "#818cf8", "#a5b4fc", "#312e81", "#c7d2fe", "#94a3b8"],
  cyan: ["#0891b2", "#22d3ee", "#67e8f9", "#164e63", "#a5f3fc", "#94a3b8"],
  teal: ["#0d9488", "#2dd4bf", "#5eead4", "#134e4a", "#99f6e4", "#94a3b8"],
  violet: ["#7c3aed", "#a78bfa", "#c4b5fd", "#4c1d95", "#ddd6fe", "#94a3b8"],
}

export const kinds: [Kind, string][] = [
  ["number", "Angka"],
  ["donut", "Donat"],
  ["pie", "Pai"],
  ["bar", "Batang"],
  ["hbar", "Horizontal"],
  ["line", "Garis"],
  ["radar", "Radar"],
]

export const fmt = (x: number, t: number) =>
  `${((x / t) * 100).toFixed(1).replace(".", ",")}%`

export function Chart({
  kind,
  data,
  colors,
}: {
  kind: Kind
  data: Item[]
  colors: string[]
}) {
  if (kind === "number")
    return (
      <div className="flex h-full flex-col items-center justify-center text-center">
        <p
          className="text-4xl font-extrabold tabular-nums"
          style={{ color: colors[0] }}
        >
          {data.reduce((sum, item) => sum + item.v, 0).toLocaleString("id-ID")}
        </p>
        <p className="mt-2 text-xs text-slate-500">
          Total nilai · {data.length} kelompok
        </p>
      </div>
    )
  const cells = data.map((_, i) => (
    <Cell key={i} fill={colors[i % colors.length]} />
  ))
  const ax = { fontSize: 10, fill: "#64748b" }
  return (
    <ResponsiveContainer>
      {kind === "donut" || kind === "pie" ? (
        <PieChart>
          <Pie
            isAnimationActive={false}
            data={data}
            dataKey="v"
            nameKey="n"
            innerRadius={kind === "donut" ? "72%" : 0}
            outerRadius="100%"
            stroke="#fff"
            strokeWidth={2}
            startAngle={90}
            endAngle={-270}
          >
            {cells}
          </Pie>
          <Tooltip />
        </PieChart>
      ) : kind === "bar" ? (
        <BarChart data={data}>
          <XAxis dataKey="n" tick={false} axisLine={false} />
          <Tooltip />
          <Bar dataKey="v" name="Jumlah" radius={[4, 4, 0, 0]}>
            {cells}
          </Bar>
        </BarChart>
      ) : kind === "hbar" ? (
        <BarChart data={data} layout="vertical">
          <XAxis type="number" hide />
          <YAxis type="category" dataKey="n" hide />
          <Tooltip />
          <Bar dataKey="v" name="Jumlah" radius={[0, 4, 4, 0]}>
            {cells}
          </Bar>
        </BarChart>
      ) : kind === "line" ? (
        <LineChart data={data}>
          <XAxis dataKey="n" tick={false} axisLine={false} />
          <YAxis hide />
          <Tooltip />
          <Line
            dataKey="v"
            name="Jumlah"
            stroke={colors[0]}
            strokeWidth={2.5}
            dot={{ r: 4, fill: colors[0] }}
          />
        </LineChart>
      ) : (
        <RadarChart data={data} outerRadius="75%">
          <PolarGrid />
          <PolarAngleAxis dataKey="n" tick={ax} />
          <Tooltip />
          <Radar
            dataKey="v"
            name="Jumlah"
            stroke={colors[0]}
            fill={colors[1]}
            fillOpacity={0.4}
          />
        </RadarChart>
      )}
    </ResponsiveContainer>
  )
}

// Ubah teks seperti "Rp 15.000.000" atau "3,4" menjadi angka; null jika bukan angka.
export const num = (v: string | number) => {
  if (typeof v === "number") return v
  const t = v.replace(/rp|\s|%/gi, "")
  if (!/^-?[\d.,]+$/.test(t)) return null
  const n = Number(
    /,\d{1,2}$/.test(t)
      ? t.replace(/\./g, "").replace(",", ".")
      : t.replace(/[.,]/g, ""),
  )
  return Number.isFinite(n) ? n : null
}
export const numericCols = (rows: (string | number)[][], cols: string[]) =>
  cols
    .map((_, i) => i)
    .filter((i) => rows.length > 0 && rows.every((r) => num(r[i]) !== null))
export const series = (
  rows: (string | number)[][],
  labelCol: number,
  valueCol: number,
): Item[] =>
  rows.map((r) => ({ n: String(r[labelCol]), v: num(r[valueCol]) ?? 0 }))
