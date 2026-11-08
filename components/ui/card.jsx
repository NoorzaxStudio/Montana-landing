import { cn } from "@/lib/utils";

export function Card({ className, ...props }) {
  return <div className={cn("rounded-3xl border border-white/10 bg-white/[0.04]", className)} {...props} />;
}

export function CardContent({ className, ...props }) {
  return <div className={cn("p-6", className)} {...props} />;
}
