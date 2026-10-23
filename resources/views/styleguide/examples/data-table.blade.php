<div class="app-surface overflow-hidden">
    <div class="relative overflow-x-auto" tabindex="0" role="group" aria-label="Standard rates">
        <table class="app-table min-w-[30rem]">
            <thead>
                <tr>
                    <th scope="col" class="pl-5">Country</th>
                    <th scope="col" class="text-right">Standard</th>
                    <th scope="col" class="text-right">Reduced</th>
                    <th scope="col" class="pr-5 text-right">VAT on €100</th>
                </tr>
            </thead>
            <tbody>
                @foreach([['DE', 'Germany', '19%', '7%', '€19.00'], ['FR', 'France', '20%', '5.5%, 10%', '€20.00'], ['HU', 'Hungary', '27%', '5%, 18%', '€27.00']] as [$iso, $name, $standard, $reduced, $vat])
                    <tr class="transition-colors hover:bg-surface-subtle">
                        <td class="pl-5">
                            <span class="flex items-center gap-2.5 font-semibold text-ink">
                                <x-ui.flag :iso="$iso" />
                                {{ $name }}
                            </span>
                        </td>
                        <td class="tabular text-right font-display font-semibold text-ink">{{ $standard }}</td>
                        <td class="tabular text-right text-ink-muted">{{ $reduced }}</td>
                        <td class="tabular pr-5 text-right text-ink">{{ $vat }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
