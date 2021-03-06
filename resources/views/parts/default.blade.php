<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="description" content="">

    <meta name="author" content="">
    <link rel="stylesheet" href="{!! asset('theme/vendor/datatables/css/jquery.dataTables.min.css') !!}">
    <link rel="stylesheet" href="{!! asset('theme/vendor/datatables/css/dataTables.bootstrap.css') !!}">
    <link rel="stylesheet" href="{!! asset('theme/vendor/datatables-responsive/dataTables.responsive.css') !!}">
    <script scr="{!! asset('theme/js/jwplayer.js') !!}"></script>
    <script scr="//cdn.datatables.net/plug-ins/1.10.6/sorting/date-euro.js"></script>

    <!--    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />

    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script> -->

    <title> @yield('title-page') - {{config('app.name')}} </title>

    <meta name="csrf-token" content="{{ csrf_token() }}" />


    <!-- Bootstrap Core CSS -->

    <link href="{!! asset('theme/vendor/bootstrap/css/bootstrap.min.css') !!}" rel="stylesheet">



    <!-- MetisMenu CSS -->

    <link href="{!! asset('theme/vendor/metisMenu/metisMenu.min.css') !!}" rel="stylesheet">



    <!-- Custom CSS -->

    <link href="{!! asset('theme/dist/css/sb-admin-2.css') !!}" rel="stylesheet">



    <!-- Morris Charts CSS -->

    <link href="{!! asset('theme/vendor/morrisjs/morris.css') !!}" rel="stylesheet">



    <!-- Custom Fonts -->

    <link href="{!! asset('theme/vendor/font-awesome/css/font-awesome.min.css') !!}" rel="stylesheet" type="text/css">
   
    <link href="https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.js"></script>  
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js"></script>
    <script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>

</head>

<body>



    <div id="wrapper">



        <!-- Navigation -->

        <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">

            @include('parts.header')

            @include('parts.sidebar')

        </nav>



        <div id="page-wrapper">

            @yield('content')

        </div>

        <!-- /#page-wrapper -->



    </div>

    <!-- /#wrapper -->



    <!-- jQuery -->

    <script src="{!! asset('theme/vendor/jquery/jquery.min.js') !!}"></script>



    <!-- Bootstrap Core JavaScript -->

    <script src="{!! asset('theme/vendor/bootstrap/js/bootstrap.min.js') !!}"></script>



    <!-- Metis Menu Plugin JavaScript -->

    <script src="{!! asset('theme/vendor/metisMenu/metisMenu.min.js') !!}"></script>



    <!-- Morris Charts JavaScript 

    <script src="{!! asset('theme/vendor/raphael/raphael.min.js') !!}"></script>

    <script src="{!! asset('theme/vendor/morrisjs/morris.min.js') !!}"></script>

    <script src="{!! asset('theme/data/morris-data.js') !!}"></script>-->


    <!-- DataTables -->
    <script src="{!! asset('theme/vendor/datatables/js/jquery.dataTables.min.js') !!}"></script>
    <script src="{!! asset('theme/vendor/datatables/js/dataTables.bootstrap.min.js') !!}"></script>
    <script src="{!! asset('theme/vendor/datatables/js/dataTables.bootstrap.js') !!}"></script>
    <script src="{!! asset('theme/vendor/datatables/js/dataTables.bootstrap4.js') !!}"></script>

    <!-- Custom Theme JavaScript -->

    <script src="{!! asset('theme/dist/js/sb-admin-2.js') !!}"></script>
    <script type="text/javascript">
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
    });
    </script>
     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@9"></script>

<!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@7.12.15/dist/sweetalert2.all.min.js"></script> -->
    @yield('scripts')

    <script>
        (function($, dataTable) {
            $.extend(true, $.fn.dataTable.defaults, {
                pageLength: 25,
                paging: true,
                lengthChange: true,
                searching: true,
                ordering: true,
                info: true,
                autoWidth: true,
                aoColumnDefs: [{
                    'bSortable': false,
                    'aTargets': ['nosort']
                }],
                language: {
                    "emptyTable": "Data Kosong",
                    "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
                    "infoFiltered": "(disaring dari _MAX_ total data)",
                    "search": "Cari:",
                    "lengthMenu": "Tampilkan _MENU_ Data",
                    "zeroRecords": "Tidak Ada Data yang Ditampilkan",
                    "processing": "Silahkan Tunggu...",
                    "oPaginate": {
                        "sFirst": "Awal",
                        "sLast": "Akhir",
                        "sNext": "Selanjutnya",
                        "sPrevious": "Sebelumnya"
                    },
                },


            });
        })(jQuery, jQuery.fn.dataTable);
    </script>

</body>



</html>