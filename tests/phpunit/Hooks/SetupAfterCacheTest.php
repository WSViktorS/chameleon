<?php

namespace Skins\Chameleon\Tests\Hooks;

use Skins\Chameleon\Hooks\SetupAfterCache;

/**
 * @uses \Skins\Chameleon\Hooks\SetupAfterCache
 *
 * @ingroup Test
 *
 * @group skins-chameleon
 * @group mediawiki-databaseless
 *
 * @license GNU GPL v3+
 * @since 1.0
 *
 * @author mwjames
 */
class SetupAfterCacheTest extends \PHPUnit_Framework_TestCase {

	protected $dummyExternalModule = null;

	protected function setUp() {
		parent::setUp();

		$this->dummyExternalModule = __DIR__ . '/../Util/Fixture/' . 'externalmodule.less';
	}

	public function testCanConstruct() {

		$bootstrapManager = $this->getMockBuilder( '\Bootstrap\BootstrapManager' )
			->disableOriginalConstructor()
			->getMock();

		$configuration = array();

		$this->assertInstanceOf(
			'\Skins\Chameleon\Hooks\SetupAfterCache',
			new SetupAfterCache( $bootstrapManager, $configuration )
		);
	}

	public function testProcessWithValidExternalModuleWithoutLessVariables() {

		$bootstrapManager = $this->getMockBuilder( '\Bootstrap\BootstrapManager' )
			->disableOriginalConstructor()
			->getMock();

		$bootstrapManager->expects( $this->at( 1 ) )
			->method( 'addExternalModule' );

		$bootstrapManager->expects( $this->at( 2 ) )
			->method( 'addExternalModule' )
			->with(
				$this->equalTo( $this->dummyExternalModule ),
				$this->equalTo( '' ) );

		$bootstrapManager->expects( $this->at( 3 ) )
			->method( 'addExternalModule' )
			->with(
				$this->equalTo( $this->dummyExternalModule  ),
				$this->equalTo( 'someRemoteWeDontCheck' ) );

		$bootstrapManager->expects( $this->never() )
			->method( 'setLessVariable' );

		$mixedExternalStyleModules = array(
			$this->dummyExternalModule ,
			$this->dummyExternalModule  => 'someRemoteWeDontCheck'
		);

		$configuration = array(
			'egChameleonExternalStyleModules' => $mixedExternalStyleModules,
			'wgStyleDirectory'                => 'notTestinwgStyleDirectory',
			'wgStylePath'                     => 'notTestingwgStylePath'
		);

		$instance = new SetupAfterCache(
			$bootstrapManager,
			$configuration
		);

		$instance->process();
	}

	public function testProcessWithInvalidExternalModuleThrowsException() {

		$bootstrapManager = $this->getMockBuilder( '\Bootstrap\BootstrapManager' )
			->disableOriginalConstructor()
			->getMock();

		$bootstrapManager->expects( $this->atLeastOnce() )
			->method( 'addExternalModule' )
			->will( $this->returnValue( true ) );

		$externalStyleModules = array(
			__DIR__ . '/../Util/Fixture/' . 'externalmoduleDoesNotExist.less'
		);

		$configuration = array(
			'egChameleonExternalStyleModules' => $externalStyleModules,
			'wgStyleDirectory'                => 'notTestinwgStyleDirectory',
			'wgStylePath'                     => 'notTestingwgStylePath'
		);

		$instance = new SetupAfterCache(
			$bootstrapManager,
			$configuration
		);

		$this->setExpectedException( 'RuntimeException' );

		$instance->process();
	}

	public function testProcessWithLessVariables() {

		$bootstrapManager = $this->getMockBuilder( '\Bootstrap\BootstrapManager' )
			->disableOriginalConstructor()
			->getMock();

		$bootstrapManager->expects( $this->once() )
			->method( 'addExternalModule' )
			->will( $this->returnValue( true ) );

		$bootstrapManager->expects( $this->once() )
			->method( 'setLessVariable' )
			->with(
				$this->equalTo( 'foo' ),
				$this->equalTo( '999px') );

		$externalLessVariables = array(
			'foo' => '999px'
		);

		$configuration = array(
			'egChameleonExternalLessVariables' => $externalLessVariables,
			'wgStyleDirectory'                => 'notTestinwgStyleDirectory',
			'wgStylePath'                     => 'notTestingwgStylePath'
		);

		$instance = new SetupAfterCache(
			$bootstrapManager,
			$configuration
		);

		$instance->process();
	}

	/**
	 * @dataProvider lateSettingsProvider
	 */
	public function testProcessDoesLateSettings( $configuration, $expected )
	{

		$bootstrapManager = $this->getMockBuilder( '\Bootstrap\BootstrapManager' )
			->disableOriginalConstructor()
			->getMock();

		$defaultConfiguration = array(
			'wgStyleDirectory' => 'notTestingwgStyleDirectory',
			'wgStylePath' => 'notTestingwgStylePath'
		);

		$configurationToBeAdjusted = array();

		$instance = new SetupAfterCache(
			$bootstrapManager,
			$configuration + $defaultConfiguration
		);

		$instance->process();
		$instance->adjustConfiguration( $configurationToBeAdjusted );

		$this->assertEquals(
			$expected + $defaultConfiguration,
			$configurationToBeAdjusted
		);
	}

	/**
	 * @dataProvider adjustConfigurationProvider
	 */
	public function testAdjustConfiguration( $origConfig, $changes, $expected){

		$bootstrapManager = $this->getMockBuilder( '\Bootstrap\BootstrapManager' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SetupAfterCache(
			$bootstrapManager,
			$changes
		);

		$instance->adjustConfiguration( $origConfig );

		$this->assertEquals( $expected, $origConfig );
	}

	/**
	 * Provides test data for the lateSettings test
	 */
	public function lateSettingsProvider() {

		return array(

			array (
				array(
				),
				array(
				),
			),

			array (
				array(
					'wgVisualEditorSupportedSkins'	=> array(),
				),
				array(
					'wgVisualEditorSupportedSkins'	=> array(),
				),
			),

			array (
				array(
					'egChameleonEnableVisualEditor'	=> true,
				),
				array(
					'egChameleonEnableVisualEditor'	=> true,
				),
			),

			array (
				array(
					'egChameleonEnableVisualEditor'	=> true,
					'wgVisualEditorSupportedSkins'	=> array( 'foo'),
				),
				array(
					'egChameleonEnableVisualEditor'	=> true,
					'wgVisualEditorSupportedSkins'	=> array( 'foo', 'chameleon' ),
				),
			),

			array (
				array(
					'egChameleonEnableVisualEditor'	=> true,
					'wgVisualEditorSupportedSkins'	=> array( 'foo', 'chameleon' ),
				),
				array(
					'egChameleonEnableVisualEditor'	=> true,
					'wgVisualEditorSupportedSkins'	=> array( 'foo', 'chameleon' ),
				),
			),

			array (
				array(
					'egChameleonEnableVisualEditor'	=> false,
					'wgVisualEditorSupportedSkins'	=> array( 'chameleon', 'foo' => 'chameleon', 'foo' ),
				),
				array(
					'egChameleonEnableVisualEditor'	=> false,
					'wgVisualEditorSupportedSkins'	=> array( 1 => 'foo' ),
				),
			),

		);
	}

	public function adjustConfigurationProvider() {
		return array(
			array(
				array(
					'key1' => 'value1',
					'key2' => 'value2',
				),
				array(
					'key2' => 'value2changed',
					'key3' => 'value3changed',
				),
				array(
					'key1' => 'value1',
					'key2' => 'value2changed',
					'key3' => 'value3changed',
				),
			),
		);
	}
}