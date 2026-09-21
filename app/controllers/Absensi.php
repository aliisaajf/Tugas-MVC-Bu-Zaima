<?php

class Absensi extends Controller {
    
    public function index() {
        // Karena datanya kosong, kita langsung memanggil file view-nya saja
        $this->view('absensi_view');
    }
}