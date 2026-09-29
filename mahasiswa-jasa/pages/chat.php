<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/review_functions.php';
require_once __DIR__ . '/../includes/chat_functions.php';

requireLogin();
$me = currentUserId();

// Mulai chat dengan user tertentu (dari halaman jasa / pesanan)
if (isset($_GET['user'])) {
    $roomId = getOrCreateRoom($me, (int) $_GET['user'], (int) ($_GET['jasa'] ?? 0));
    if (!$roomId) {
        setFlash('danger', 'Tidak bisa memulai chat dengan pengguna ini.');
        redirect('pages/chat.php');
    }
    redirect('pages/chat.php?room=' . $roomId);
}

$rooms = getUserRooms($me);
$active = null;
if (isset($_GET['room'])) {
    $active = getRoomForUser((int) $_GET['room'], $me);
    if (!$active) {
        setFlash('danger', 'Percakapan tidak ditemukan.');
        redirect('pages/chat.php');
    }
}

$messages = [];
$lastId = 0;
if ($active) {
    $messages = getRoomMessages((int) $active['id']);
    markRoomRead((int) $active['id'], $me);
    $lastId = $messages ? (int) end($messages)['id'] : 0;
    // badge unread untuk room aktif sudah dianggap terbaca
    foreach ($rooms as &$r) { if ((int) $r['id'] === (int) $active['id']) { $r['unread'] = 0; } }
    unset($r);
}

$pageTitle = 'Chat';
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/chat.css">

<div class="chat-page">
<div class="chat-layout <?= $active ? 'has-room' : '' ?>">

    <aside class="chat-list">
        <h2>Chat</h2>
        <div class="chat-filter">
            <button type="button" class="active" data-filter="all">Semua</button>
            <button type="button" data-filter="unread">Belum Dibaca</button>
        </div>
        <div class="chat-rooms" id="chatRooms">
            <?php if (!$rooms): ?>
                <div class="chat-empty">Belum ada percakapan.<br>Mulai dari halaman jasa dengan tombol “Chat Penyedia”.</div>
            <?php endif; ?>
            <?php foreach ($rooms as $r): ?>
                <a class="chat-room <?= $active && (int) $active['id'] === (int) $r['id'] ? 'active' : '' ?>"
                   href="<?= BASE_URL ?>/pages/chat.php?room=<?= (int) $r['id'] ?>" data-unread="<?= (int) $r['unread'] > 0 ? 1 : 0 ?>">
                    <div class="chat-avatar"><?= avatarHtml($r['other_foto'], $r['other_nama']) ?></div>
                    <div class="chat-room-body">
                        <div class="chat-room-top"><strong><?= clean($r['other_nama']) ?></strong><span class="ago"><?= clean(timeAgoIndo($r['last_at'])) ?></span></div>
                        <?php if ($r['judul']): ?><div class="chat-room-svc"><?= clean($r['judul']) ?></div><?php endif; ?>
                        <div class="chat-room-last"><?= $r['last_msg'] !== null ? clean(mb_strimwidth($r['last_msg'], 0, 60, '…')) : 'Belum ada pesan' ?></div>
                    </div>
                    <?php if ((int) $r['unread'] > 0): ?><span class="unread-dot"><?= (int) $r['unread'] ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <section class="chat-main">
        <?php if (!$active): ?>
            <div class="chat-empty">Pilih percakapan di sebelah kiri untuk mulai mengobrol.</div>
        <?php else: ?>
            <div class="chat-head">
                <a class="chat-back" href="<?= BASE_URL ?>/pages/chat.php" aria-label="Kembali">←</a>
                <div class="chat-avatar"><?= avatarHtml($active['other_foto'], $active['other_nama']) ?></div>
                <div>
                    <strong><a href="<?= BASE_URL ?>/pages/profile.php?id=<?= (int) $active['other_id'] ?>"><?= clean($active['other_nama']) ?></a></strong>
                    <?php if ($active['judul']): ?><a class="svc" href="<?= BASE_URL ?>/pages/service_detail.php?id=<?= (int) $active['jasa_id'] ?>"><?= clean($active['judul']) ?></a><?php endif; ?>
                </div>
            </div>

            <div class="chat-messages" id="chatMessages">
                <?php if (!$messages): ?><div class="chat-empty" id="chatEmpty">Belum ada pesan. Sapa <?= clean(explode(' ', $active['other_nama'])[0]) ?> 👋</div><?php endif; ?>
                <?php foreach ($messages as $m): $mine = (int) $m['sender_id'] === $me; ?>
                    <div class="bubble <?= $mine ? 'mine' : 'theirs' ?>"><?= clean($m['pesan']) ?><span class="t"><?= date('H:i', strtotime($m['created_at'])) ?></span></div>
                <?php endforeach; ?>
            </div>

            <div class="chat-error" id="chatError"></div>
            <form class="chat-form" id="chatForm" autocomplete="off">
                <input type="text" id="chatInput" maxlength="2000" placeholder="Tulis pesan..." required>
                <button class="btn btn-primary" type="submit">Kirim</button>
            </form>
        <?php endif; ?>
    </section>
</div>
</div>

<script>
// Filter daftar: semua / belum dibaca
document.querySelectorAll('.chat-filter button').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.chat-filter button').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        var onlyUnread = btn.dataset.filter === 'unread';
        document.querySelectorAll('#chatRooms .chat-room').forEach(function (el) {
            el.style.display = (onlyUnread && el.dataset.unread !== '1') ? 'none' : '';
        });
    });
});
</script>

<?php if ($active): ?>
<script>
(function () {
    var roomId = <?= (int) $active['id'] ?>;
    var csrf = <?= json_encode(csrfToken()) ?>;
    var apiUrl = <?= json_encode(BASE_URL . '/pages/chat_api.php') ?>;
    var lastId = <?= $lastId ?>;
    var box = document.getElementById('chatMessages');
    var form = document.getElementById('chatForm');
    var input = document.getElementById('chatInput');
    var errBox = document.getElementById('chatError');

    function scrollDown() { box.scrollTop = box.scrollHeight; }
    scrollDown();

    function addMessage(m) {
        if (m.id <= lastId) return;          // sudah tampil
        lastId = m.id;
        var empty = document.getElementById('chatEmpty');
        if (empty) empty.remove();
        var div = document.createElement('div');
        div.className = 'bubble ' + (m.mine ? 'mine' : 'theirs');
        div.appendChild(document.createTextNode(m.text));   // textContent -> aman dari XSS
        var t = document.createElement('span');
        t.className = 't';
        t.textContent = m.time;
        div.appendChild(t);
        box.appendChild(div);
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = input.value.trim();
        if (!text) return;
        errBox.textContent = '';
        var body = new URLSearchParams({ room: roomId, csrf: csrf, pesan: text });
        input.value = '';
        fetch(apiUrl, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) { errBox.textContent = data.error; input.value = text; return; }
                addMessage(data.message);
                scrollDown();
            })
            .catch(function () { errBox.textContent = 'Gagal mengirim, periksa koneksi kamu.'; input.value = text; });
    });

    function poll() {
        if (document.hidden) return;
        fetch(apiUrl + '?room=' + roomId + '&after=' + lastId, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.messages || !data.messages.length) return;
                var nearBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 120;
                data.messages.forEach(addMessage);
                if (nearBottom) scrollDown();
            })
            .catch(function () {});
    }
    setInterval(poll, 3000);
})();
</script>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
