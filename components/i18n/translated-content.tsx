import messages from "@/data/messages.json";
import type { LocaleMessages } from "@/types/i18n";

const localeMessages = messages as LocaleMessages;

interface TranslatedContentProps {
  id: string;
}

export function TranslatedContent({ id }: TranslatedContentProps) {
  return (
    <span
      style={{ display: "contents" }}
      dangerouslySetInnerHTML={{ __html: localeMessages.fa[id] ?? "" }}
    />
  );
}
