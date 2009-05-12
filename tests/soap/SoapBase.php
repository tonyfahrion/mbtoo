<?php
# MantisBT - a php based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package Tests
 * @subpackage UnitTests
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright (C) 2002 - 2009  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 */

require_once 'PHPUnit/Framework.php';

$t_root_path = dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR;

/**
 * Test cases for SoapEnum class.
 */
class SoapBase extends PHPUnit_Framework_TestCase {
	protected $client;
	protected $userName = 'administrator';
	protected $password = 'root';

    protected function setUp()
    {
    	if (!isset($GLOBALS['MANTIS_TESTSUITE_SOAP_ENABLED']) ||
			!$GLOBALS['MANTIS_TESTSUITE_SOAP_ENABLED']) {
			$this->markTestSkipped( 'The Soap tests are disabled.' );
		}
    
		$this->client = new
		    SoapClient(
		       $GLOBALS['MANTIS_TESTSUITE_SOAP_HOST'],
		        array(  'trace'      => true,
		                'exceptions' => true,
		             )
		     
		    );
    }

    protected function getProjectId() {
    	return 1;	
    }

    protected function getCategory() {
 		return 'General';   	
    }

	protected function getIssueToAdd( $testCase ) {
		return array(
				'summary' => $testCase . ': test issue: ' . rand(1, 1000000),
				'description' => 'description of test issue.',
				'project' => array( 'id' => $this->getProjectId() ),
				'category' => $this->getCategory() );
	}
}
