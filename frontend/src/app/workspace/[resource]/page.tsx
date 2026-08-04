import { notFound } from 'next/navigation';
import { ResourceWorkspace } from '@/components/resource-workspace';
import { resourceMap } from '@/lib/resources';

export default async function ResourcePage({ params }: { params: Promise<{ resource: string }> }) {
  const { resource: slug } = await params;
  const resource = resourceMap.get(slug);
  if (!resource) notFound();
  return <ResourceWorkspace resource={resource} />;
}
