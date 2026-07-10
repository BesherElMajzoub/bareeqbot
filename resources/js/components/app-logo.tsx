export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-9 items-center justify-center overflow-hidden rounded-lg bg-white p-0.5">
                <img
                    src="/image.png"
                    alt="بريق"
                    className="size-full object-contain"
                />
            </div>
            <div className="ml-2 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold text-foreground">
                    بريق
                </span>
                <span className="truncate text-xs text-muted-foreground">
                    أتمتة المحادثات
                </span>
            </div>
        </>
    );
}
