<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\PageBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

class CreateSiteCommand extends BaseCommand
{
    public function configure()
    {
        $this->setName('sonata:page:create-site');

        $this->addOption('confirmation', null, InputOption::VALUE_OPTIONAL, 'Ask confirmation before generating the site', true);

        $this->addOption('enabled', null, InputOption::VALUE_OPTIONAL, 'Site.enabled', false);
        $this->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Site.name', null);
        $this->addOption('relativePath', null, InputOption::VALUE_OPTIONAL, 'Site.relativePath', null);
        $this->addOption('domain', null, InputOption::VALUE_OPTIONAL, 'Site.domain', null);
        $this->addOption('enabledFrom', null, InputOption::VALUE_OPTIONAL, 'Site.enabledFrom', null);
        $this->addOption('enabledTo', null, InputOption::VALUE_OPTIONAL, 'Site.enabledTo', null);
        $this->addOption('default', null, InputOption::VALUE_OPTIONAL, 'Site.default', null);

        $this->setDescription('Create a site');

        $this->setHelp(<<<EOT
The <info>sonata:page:create-site</info> command create a new site entity.

EOT
    );

    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        $values = array(
            'name'=> null,
            'domain'=> null,
            'relativePath'=> null,
            'enabled'=> null,
            'enabledFrom'=> null,
            'enabledTo'=> null,
            'default'=> null,
        );

        foreach($values as $name => $value) {
            $values[$name] = $input->getOption($name);

            while($values[$name] == null) {
                $values[$name] = $dialog->ask($output, sprintf("Please define a value for <info>Site.%s</info> : ", $name));
            }
        }

        // create the object
        $site = $this->getSiteManager()->create();

        $site->setName($values['name']);
        $site->setRelativePath($values['relativePath']);
        $site->setDomain($values['domain']);
        $site->setEnabledFrom(new \DateTime($values['enabledFrom']));
        $site->setEnabledTo(new \DateTime($values['enabledTo']));
        $site->setIsDefault($values['default']);
        $site->setEnabled(in_array($values['enabled'], array('true', 1, '1')));

        $output->writeln(<<<INFO

Creating website with the following information :
  <info>name</info> : {$site->getName()}
  <info>site</info> : http(s)://{$site->getDomain()}{$site->getRelativePath()}
  <info>enabled</info> :  {$site->getEnabledFrom()->format('r')} => {$site->getEnabledto()->format('r')}

INFO
);

        $this->getSiteManager()->save($site);

        $output->writeln(' OK !');
    }
}