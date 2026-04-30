<!-- bootstrap js -->
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>

<!-- jquery -->
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>

<!-- plugins -->
<script src="{{ asset('assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
<script src="{{ asset('assets/plugins/metismenu/metisMenu.min.js') }}"></script>
<script src="{{ asset('assets/plugins/simplebar/js/simplebar.min.js') }}"></script>
<script src="{{ asset('assets/plugins/peity/jquery.peity.min.js') }}"></script>

<script>
    $(".data-attributes span").peity("donut");
</script>

<!-- main scripts -->
<script src="{{ asset('assets/js/main.js') }}"></script>
<script>
    const userList = document.querySelector(".user-list");

    if (userList) {
        new PerfectScrollbar(userList);
    }
</script>
