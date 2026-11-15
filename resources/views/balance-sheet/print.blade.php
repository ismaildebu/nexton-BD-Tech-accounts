{{--
    Print-friendly Balance Sheet.

    It reuses the PDF layout (same report, same numbers) so the printed and
    the downloaded versions can never differ, and opens the browser print
    dialog automatically.
--}}
@include('balance-sheet.pdf')

<script>
    window.addEventListener('load', function () {
        window.print();
    });
</script>
