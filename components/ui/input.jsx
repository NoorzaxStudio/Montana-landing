import { cn } from "@/lib/utils";

export function Input({ className, type = "text", ...props }) {
  return (
    <input
      type={type}
      className={cn(
        "h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-ink outline-none transition placeholder:text-slate-400 focus:border-gold-dark focus:ring-4 focus:ring-gold/15",
        className
      )}
      {...props}
    />
  );
}
