<@php

namespace {namespace};

use CodeIgniter\Database\Migration;

class {class} extends Migration
{
	protected $DBGroup = '<?= $DBGroup ?>';

	public function up()
	{
		$this->forge->addField([
			'id'          => [
				'type'           => 'INTEGER',
				'auto_increment' => true,
			],
			'queue'  => [
				'type'       => 'VARCHAR',
				'constraint' => 255,
			],
			'status'      => [
				'type'       => 'TINYINT',
				'constraint' => 1,
				'unsigned'   => true,
			],
			'weight'      => [ 'type' => 'INTEGER' ],
			'attempts'    => [
				'type'     => 'INTEGER',
				'unsigned' => true,
			],
			'available_at'     => [ 'type' => 'DATETIME' ],
			'data'             => [ 'type' => 'TEXT' ],
			'progress_current' => [ 'type' => 'INT' ],
			'progress_total'   => [ 'type' => 'INT' ],
			'error'            => [ 'type' => 'TEXT' ],
			'created_at'       => [ 'type' => 'DATETIME' ],
			'updated_at'       => [ 'type' => 'DATETIME' ],
		]);
		$this->forge->addKey('id', true);
		//      $this->forge->addKey(['weight', 'id', 'queue', 'status', 'available_at']);
		$this->forge->createTable('<?= $table ?>', true);
	}

	public function down()
	{
		$this->forge->dropTable('<?= $table ?>', true);
	}
}
