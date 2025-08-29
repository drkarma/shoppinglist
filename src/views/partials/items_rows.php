<?php if (empty($items)): ?>
  <tr><td colspan="5" class="text-center text-muted p-4">Inga varor ännu – lägg till ovan.</td></tr>
<?php else: ?>
  <?php foreach ($items as $item): 
    $costNum = ($item['cost'] !== null && $item['cost'] !== '') ? (float)$item['cost'] : null;
    $costDisp = ($costNum === null) ? '' : number_format($costNum, 2, ',', ' ') . ' kr';
  ?>
    <tr class="<?= $item['checked'] ? 'done' : '' ?>" data-id="<?= (int)$item['id'] ?>">
      <td class="text-center">
        <form method="POST" action="?action=toggle_item">
          <input type="hidden" name="list_id" value="<?= htmlspecialchars($list['id']) ?>">
          <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
          <input class="form-check-input" type="checkbox" name="checked" onchange="this.form.submit()" <?= $item['checked'] ? 'checked' : '' ?>>
        </form>
      </td>
      <td data-edit="name"><?= htmlspecialchars($item['name']) ?></td>
      <td data-edit="quantity"><?= htmlspecialchars($item['quantity']) ?></td>
      <td data-edit="cost" data-cost="<?= $costNum === null ? '' : htmlspecialchars((string)$costNum) ?>"><?= htmlspecialchars($costDisp) ?></td>
      <td class="actions text-center">
        <form method="POST" action="?action=delete_item" onsubmit="return confirm('Ta bort raden?')">
          <input type="hidden" name="list_id" value="<?= htmlspecialchars($list['id']) ?>">
          <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
          <button class="btn btn-sm btn-outline-danger" title="Ta bort">🗑️</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
<?php endif; ?>
