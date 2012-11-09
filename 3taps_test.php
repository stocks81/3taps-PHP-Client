<?

DEFINE('API_KEY', '75c8e3851e874fe692a8199022a06645');

require('3taps.php'); 
require('simpletest/autorun.php'); 


class TestOfSearchClient extends UnitTestCase {

	private $client;
	private $params = array(
			'rpp' => 4,
			'text' => 'honda',
			'source' => 'CRAIG'
	);

	function __construct() {
		parent::__construct('3taps Search API test');

		$this->client = new threeTapsClient(API_KEY);
		$this->client->debug = true;
	}
	
	function testSearch() {
		$results = $this->client->search->search($this->params);
		$this->assertTrue($results['numResults'] > 0);
	}

	function testSummary() {
		$results = $this->client->search->summary($this->params);
		$this->assertTrue($results['totals'] > 0);
	}

	function testCount() {
		$result = $this->client->search->count($this->params);
		$this->assertTrue(count($result['count']) > 0);
	}
}

class TestOfReferenceClient extends UnitTestCase {

	private $client;

	function __construct() {
		parent::__construct('3taps Reference API test');

		$this->client = new threeTapsClient(API_KEY);
		$this->client->debug = true;
	}
	
	function testCategories() {
		$results = $this->client->reference->categories();
		$this->assertTrue(count($results) > 0);
	}
}

