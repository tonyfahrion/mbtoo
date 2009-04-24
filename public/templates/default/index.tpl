{*
# MantisBT - a php based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.
*}


{* we won't display html-header stuf if it wasn't requested! *}
{if isset( $mantis_show_html_wrapper ) }
	{include file=header.tpl}
{/if}

{if isset( $mantis_show_body_head ) }
	{include file=html_body_head.tpl}
{/if}

{* here we have our importend content of the requested module *}
{include file="pages/$mantis_module.tpl"}

{if isset( $mantis_show_html_wrapper ) }
	{include file=footer.tpl}
{/if}
