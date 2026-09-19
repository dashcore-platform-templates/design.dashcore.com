@props(['design', 'register' => 'light'])
{{-- Everything a block draws from, derived in one place. Three callers used to
     build this by hand and one of them read the motif off a different object. --}}
@php ['t' => $t, 'motif' => $motif, 'shell' => $shell] = \App\Design\Blocks::context($design); @endphp
{{-- @ds-audit-ignore-file a drawing of section 10, where none of the rest of the standard applies: no utility classes, no custom properties, pixel values inline, because that is all an email client honours. --}}
{{--
    Email, from standard/10. The one surface where the rest of the standard
    cannot apply: no custom properties, no utility classes, no web fonts, no
    second typeface and no dark register.

    This is a picture of a real email, so it is deliberately plainer than every
    other screen here. The body face only, one column at 600px, 16px body text
    rather than the 14px an app screen uses, and one button.
--}}
@php
    $name = $design->name();
    $initial = mb_substr($name, 0, 1);
@endphp
<div class="min-h-[720px] bg-(--ds-sunken) p-(--ds-gutter)">
    <div class="mx-auto max-w-[760px] space-y-(--ds-section)">

        {{-- Why the preheader exists. Without it the client shows whatever it finds first. --}}
        <section>
            <p class="ds-label mb-1.5">In the inbox list</p>
            <div class="overflow-hidden rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface)">
                <div class="flex items-start gap-3 border-b border-(--ds-border) p-(--ds-card)">
                    <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-(--radius-sm) bg-(--ds-accent) text-sm font-medium text-(--ds-on-accent)">{{ $initial }}</span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-(--ds-text-strong)">{{ $name }}</p>
                        <p class="truncate text-sm text-(--ds-text)">Your invoice is ready</p>
                        <p class="truncate text-sm text-(--ds-text-muted)">£84.00 for Tuesday's visit, due in 14 days.</p>
                    </div>
                    <span class="ms-auto shrink-0 text-xs text-(--ds-text-muted)">09:12</span>
                </div>
                <div class="flex items-start gap-3 p-(--ds-card) opacity-60">
                    <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-(--radius-sm) bg-(--ds-border) text-sm font-medium text-(--ds-text-muted)">A</span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-(--ds-text-strong)">Another product</p>
                        <p class="truncate text-sm text-(--ds-text)">Your invoice is ready</p>
                        <p class="truncate text-sm text-(--ds-text-muted)">Another product Another product View in browser Unsubscribe</p>
                    </div>
                    <span class="ms-auto shrink-0 text-xs text-(--ds-text-muted)">09:12</span>
                </div>
            </div>
            <p class="mt-1.5 text-xs text-(--ds-text-muted)">The second one has no preheader, so the client picked the first words in the body.</p>
        </section>

        {{-- The email itself. 600px, one column, one action. --}}
        <section>
            <p class="ds-label mb-1.5">The email</p>
            <div class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-page) p-8">
                <div class="mx-auto w-[600px] max-w-full rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface)">
                    <div class="border-b border-(--ds-border) px-8 py-5">
                        <span class="text-[16px] font-medium text-(--ds-text-strong)">{{ $name }}</span>
                    </div>

                    <div class="px-8 py-7">
                        <h1 class="text-[20px] font-semibold leading-[1.3] text-(--ds-text-strong)">Your invoice is ready</h1>
                        <p class="mt-4 text-[16px] leading-[1.5] text-(--ds-text)">
                            Thank you for bringing Pepper in on Tuesday. The invoice for the visit is £84.00, and it is due in 14 days.
                        </p>
                        <p class="mt-3 text-[16px] leading-[1.5] text-(--ds-text)">
                            You can pay it online, or call the practice and we will take it over the phone.
                        </p>

                        {{-- A table cell carries the background, not a styled anchor.
                             Outlook renders a CSS-styled link as plain text. --}}
                        <table class="mt-6" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                                <td class="rounded-(--radius-lg) bg-(--ds-accent) px-5 py-3 text-center">
                                    <span class="text-[16px] font-medium text-(--ds-on-accent)">Pay the invoice</span>
                                </td>
                            </tr>
                        </table>

                        <p class="mt-6 text-[16px] leading-[1.5] text-(--ds-text-muted)">
                            If the button does not work, the address is
                            <span class="break-all text-(--ds-accent-content)">https://calder-street.example/invoices/4182</span>
                        </p>
                    </div>

                    <div class="border-t border-(--ds-border) px-8 py-5">
                        <p class="text-[12px] leading-[1.5] text-(--ds-text-muted)">
                            Sent by {{ $name }} because you are registered as Pepper's owner.<br>
                            14 Calder Street. <span class="text-(--ds-accent-content)">Manage what we send you</span>.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- A status email. The status colour is text on the line that carries
             the status, and the button stays the accent. --}}
        <section>
            <p class="ds-label mb-1.5">A status email</p>
            <div class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-page) p-8">
                <div class="mx-auto w-[600px] max-w-full rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface)">
                    <div class="border-b border-(--ds-border) px-8 py-5">
                        <span class="text-[16px] font-medium text-(--ds-text-strong)">{{ $name }}</span>
                    </div>
                    <div class="px-8 py-7">
                        <p class="text-[16px] font-medium text-(--ds-success)">Payment received</p>
                        <h1 class="mt-1 text-[20px] font-semibold leading-[1.3] text-(--ds-text-strong)">£84.00, Tuesday's visit</h1>
                        <p class="mt-4 text-[16px] leading-[1.5] text-(--ds-text)">
                            Nothing more to do. The receipt is on your account if you need it for insurance.
                        </p>
                    </div>
                    <div class="border-t border-(--ds-border) px-8 py-5">
                        <p class="text-[12px] leading-[1.5] text-(--ds-text-muted)">Sent by {{ $name }} because you paid an invoice.</p>
                    </div>
                </div>
            </div>
        </section>

    </div>
</div>
