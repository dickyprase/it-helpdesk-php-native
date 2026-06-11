<?php
include "header.php";
?>
<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">

            <div class="card mb-4 shadow p-3 mb-5 bg-body rounded">
                <div class="card-header">
                    <i class="fas fa-trophy me-1"></i>
                    Tiket dalam Antrian
                </div>
                <div class="card-body">
                    <table id="datatablesSimpleTicket">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No Tiket</th>
                                <th>Nama Support</th>
                                <th>Divisi</th>
                                <th>Tanggal</th>
                                <th>Deskripsi Kendala</th>
                                <th>Bukti Kendala</th>
                                <th>Tingkat Prioritas</th>
                                <th>Tingkat Kesulitan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>#tkt00001</td>
                                <td>Rahmat</td>
                                <td>Administrasi</td>
                                <td>24/04/2026</td>
                                <td>Lorem ipsum dolor sit amet consectetur, adipisicing elit. Eveniet autem provident similique consequuntur explicabo facere et ipsa quidem, quae repellat neque expedita! Alias necessitatibus possimus aliquid vitae deserunt amet, quasi totam! Numquam similique qui tempore aspernatur dolore, accusantium exercitationem inventore nihil fuga nulla, reiciendis tempora reprehenderit velit vero nisi suscipit.</td>
                                <td>
                                    <a href="#" class="btn btn-warning" target="_blank"><i class="fas fa-file-image"></i></a>
                                </td>
                                <td>
                                    <span class="mb-3 badge-status danger">Prioritas</span>
                                    <span class="mb-3 badge-status warning">Sedang</span>
                                    <span class="mb-3 badge-status success">Ringan</span>
                                </td>
                                <td>
                                    <span class="mb-3 badge-status danger">Sulit</span>
                                    <span class="mb-3 badge-status warning">Sedang</span>
                                    <span class="mb-3 badge-status success">Ringan</span>
                                </td>
                                <td>
                                    <a href="chat-user.php" class="btn btn-danger" style="font-size: 15px;">Buka Chat</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php
    include "footer.php";
    ?>