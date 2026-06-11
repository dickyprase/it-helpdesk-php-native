<?php
include "header.php";
?>
<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid">

            <div class="row mt-4 d-flex justify-content-center">

                <div class="col">
                    <div class="card shadow p-3 mb-4 bg-body rounded">

                        <div class="card-body text-center rounded" style="font-weight: bold; font-size: 30px; color: #fff; background-color: #8c57ff;">Form Tiket Baru</div>

                        <hr>

                        <form>

                            <div class="mb-3">
                                <label for="#" class="form-label">No. Tiket :</label>
                                <input type="text" class="form-control" name="nomor" disabled>
                                <div class="form-text">Terisi Otomatis</div>
                            </div>

                            <div class="mb-3">
                                <label for="#" class="form-label">Divisi :</label>
                                <select class="form-select" aria-label="Default select example">
                                    <option selected>-- Pilih Divisi --</option>
                                    <option value="1">Produksi</option>
                                    <option value="2">Marketing</option>
                                    <option value="3">Admin</option>
                                </select>
                            </div>

                            <label for="#" class="form-label">Deskripsi Kendala :</label>
                            <div class="form-floating mb-3">
                                <textarea class="form-control" id="floatingTextarea2" style="height: 300px"></textarea>
                                <label for="floatingTextarea2">Tulis Kendala Disini</label>
                            </div>

                            <div class="mb-3">
                                <label for="#" class="form-label">Bukti Kendala :</label>
                                <input type="file" class="form-control" name="image">
                                <div class="form-text">Sertakan Foto / Gambar Kendala yang Sedang Terjadi</div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-success">Kirim Kendala</button>
                            </div>

                        </form>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <?php
    include "footer.php";
    ?>